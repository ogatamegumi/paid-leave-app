## ER図

```mermaid
erDiagram
    users {
        bigint id PK "ユーザーID"
        string name "氏名"
        string email "メールアドレス"
        string role "権限（admin / user）"
        date joined_on "入社日"
        datetime created_at
        datetime updated_at
    }

    paid_leave_requests {
        bigint id PK "有給申請ID"
        bigint user_id FK "申請者ユーザーID"
        date start_date "取得開始日"
        date end_date "取得終了日"
        decimal requested_days "申請日数"
        string status "申請状態（pending / approved / rejected / canceled）"
        datetime created_at "申請日時"
        datetime updated_at
    }

    paid_leave_grants {
        bigint id PK "有給付与ID"
        bigint user_id FK "付与対象ユーザーID"
        date start_date "利用開始日"
        date end_date "失効日"
        decimal days "付与日数"
        string unit "単位（day / half / hour）"
        string status "付与状態（active / expired）"
        datetime created_at
        datetime updated_at
    }

    paid_leave_usages {
        bigint id PK "有給消費ID"
        bigint paid_leave_grant_id FK "参照する有給付与ID"
        bigint paid_leave_request_id FK "参照する有給申請ID"
        decimal used_days "消費日数"
        datetime created_at "消費確定日時"
    }

    users ||--o{ paid_leave_requests : "申請する"
    users ||--o{ paid_leave_grants : "付与される"

    paid_leave_requests ||--o{ paid_leave_usages : "消費内訳"
    paid_leave_grants ||--o{ paid_leave_usages : "消費元"
```

## 環境構築
1. Dockerコンテナを起動
```
# （必要に応じて）既存のコンテナ・ボリュームを削除
docker compose -f docker/docker-compose.yml down -v

# イメージをビルド
docker compose -f docker/docker-compose.yml build --no-cache

# 依存関係をインストール（BE）
docker compose -f docker/docker-compose.yml run --rm app composer install

# 依存関係をインストール（FE）
docker compose -f docker/docker-compose.yml run --rm node npm install

# Laravelのアプリケーションキーを生成
docker compose -f docker/docker-compose.yml run --rm app php artisan key:generate

# DBマイグレーション
docker compose -f docker/docker-compose.yml run --rm app php artisan migrate

# コンテナを起動
docker compose -f docker/docker-compose.yml up -d
```

2. ローカル環境にアクセス

【FE】http://localhost:5173/

【BE】http://localhost:8000/


## その他コマンド
- 日々の操作系
```
# docker compose down
docker compose -f docker/docker-compose.yml down

# マイグレーションファイルを作成する
docker compose -f docker/docker-compose.yml run --rm app php artisan make:migration [ファイル名]

# マイグレーションを実行
docker compose -f docker/docker-compose.yml run --rm app php artisan migrate
```

- DB（PostgresSQL）関連
```
# Dockerから接続する
docker exec -it paid_leave_db psql -U app_user -d paid_leave

# ホストから接続する
psql -h 127.0.0.1 -p 55432 -U app_user -d paid_leave
```

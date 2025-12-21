## ER図

```mermaid
erDiagram
    users {
        bigint id PK "ユーザーID"
        string name "ユーザー名"
        string email "メールアドレス"
        string role "権限（admin / member）"
        date joined_on "入社日"
        datetime created_at "作成日時"
        datetime updated_at "更新日時"
    }

    paid_leave_requests {
        bigint id PK "有給申請ID"
        bigint user_id FK "申請者ユーザーID"
        decimal requested_days "申請日数"
        string unit "有給単位（day / half / hour）"
        date start_date "休暇開始日"
        date end_date "休暇終了日"
        string status "申請ステータス（pending / approved / rejected / cancelled）"
        text reason "申請理由"
        datetime approved_at "承認日時"
        bigint approved_by FK "承認者ユーザーID"
        datetime created_at "申請日時"
        datetime updated_at "更新日時"
    }

    paid_leave_grants {
        bigint id PK "有給付与ID"
        bigint user_id FK "付与対象ユーザーID"
        bigint paid_leave_request_id FK "元となった有給申請ID"
        date start_date "有給利用開始日"
        date end_date "有給失効日"
        decimal days "付与日数"
        string unit "有給単位（day / half / hour）"
        string status "付与ステータス（active / expired / revoked）"
        datetime created_at "作成日時"
        datetime updated_at "更新日時"
    }

    users ||--o{ paid_leave_requests : "申請する"
    users ||--o{ paid_leave_grants : "付与される"
    paid_leave_requests ||--o{ paid_leave_grants : "承認後に生成"

```

## 環境構築
### 1. Dockerコンテナを起動
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

### 2. ローカル環境にアクセス
【FE】http://localhost:5173/

【BE】http://localhost:8000/


## その他コマンド
```
# docker compose down
docker compose -f docker/docker-compose.yml down
```

### DB（PostgresSQL）
```
# Dockerから接続する
docker exec -it paid_leave_db psql -U app_user -d paid_leave

# ホストから接続する
psql -h 127.0.0.1 -p 55432 -U app_user -d paid_leave
```

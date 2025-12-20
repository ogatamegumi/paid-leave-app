## 環境構築
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

## その他コマンド
```
# コンテナ、ネットワークを削除
docker compose -f docker/docker-compose.yml down
```
# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 概要

"MultiBook" のLaravel 11（PHP 8.2+）backend。Google OAuthで認証したユーザーが、Item・Imageを含むBookを管理する図鑑アプリ。

Docker構成は別リポジトリ `multibook-docker` で管理している。

## よく使うコマンド

```bash
# 開発サーバー起動（server, queue, logs, viteをまとめて起動）
composer run dev

# 全テスト実行
php artisan test

# 特定のテストファイルを実行
php artisan test tests/Feature/BookTest.php

# 特定のテストメソッドを実行
php artisan test --filter indexWithoutParameters

# コードスタイル修正（Laravel Pint）
./vendor/bin/pint

# OpenAPI YAML生成
./vendor/bin/openapi app/Http/ -o ../api-docs/openapi.yml

# migrate & seed
php artisan migrate
php artisan db:seed
```

## アーキテクチャ

### データモデル

```
User
 └── Book (user_id)
      ├── Kind (book_id)   — 図鑑内のカテゴリ（種類）
      └── Item (book_id, kind_id)
           └── Image (item_id)

UserProvider — UserとOAuthプロバイダー（Google）の紐付け
```

### Controllers

全コントローラーは `BaseController` を継承。`BaseController` が提供するもの：
- 統一されたJSONレスポンスヘルパー（`customIndexResponse`, `customStoreResponse` など）
- `processAndCompressImage()` — S3アップロード前に1000×1000px以内にリサイズし、1MB以下に圧縮
- `getImageExtension()` — MIMEタイプから拡張子を取得
- OpenAPIのトップレベルアノテーション `#[OA\Info]`, `#[OA\Server]`

全Form Requestは `BaseRequest`（`FormRequest` のサブクラス）を継承。
全Eloquent Modelは `BaseModel` を継承。

### 認証

Laravel Socialite を使ったGoogle OAuthフロー：
- `GET /auth/google` → Googleにリダイレクト
- `GET /auth/google/callback` → User + UserProviderを作成または取得し、`Auth::login()` 後に `config('app.url')` へリダイレクト
- 個人情報（name, email）はGoogleから取得せず、ダミー値を使用

### 画像ストレージ

画像・サムネイルはAWS S3に保存。URLはEloquentアクセサで `config('app.img_endpoint')` を使って読み取り時に生成：
- `Book::getThumbnailAttribute()` → `{img_endpoint}/thumbnails/{file_name}`
- `Image::getFileNameAttribute()` → `{img_endpoint}/images/{file_name}`

PHPはPUTで `multipart/form-data` を受け取れないため、画像更新は `POST /updateImages/{image}` で代用。

### Routes

全ルートは `routes/web.php`（`api.php` は使用しない）：
- `books` — CRUD + `POST /books/{book}/thumbnail`
- `items` — CRUD
- `images` — 手動ルート（GET/POST/DELETE + `POST /updateImages/{image}`）
- `GET/POST /debug` — auth/CSRFミドルウェアなし（開発用）

### OpenAPI

`zircote/swagger-php` のPHP 8 attributeをコントローラーに直接記述。`BaseController` にトップレベルの `#[OA\Info]`, `#[OA\Server]` を定義している。

## テストのセットアップ

テスト実行には、DB名に `"test"` を含むDBを指す `.env.testing` が必要（`Tests\TestCase` で強制チェックあり）。ベースの `TestCase` は `migrate:fresh` + `db:seed` をテストクラスにつき1回だけ実行する（テストメソッドごとではない）。

`RefreshDatabase` トレイトを使いつつ、`static $migrated` フラグでmigrate/seedをクラス単位で1回に抑えている。

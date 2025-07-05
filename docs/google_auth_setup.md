# Google認証（GCP）設定手順

1. [Google Cloud Console](https://console.cloud.google.com/)にアクセスし、プロジェクトを作成
2. 「APIとサービス」>「認証情報」からOAuth 2.0 クライアントIDを作成
3. 承認済みリダイレクトURIに `http://localhost:8000/auth/google/callback` を追加
4. クライアントIDとクライアントシークレットを取得し、`.env`に設定
5. 必要に応じてOAuth同意画面を設定

# Static Blog Template

このリポジトリは、GitHub Actions で Markdown（frontmatter含む）を静的HTMLに変換し、GitHub Pages へ自動デプロイする最小限のブログテンプレートです。テンプレートとしてリポジトリをコピーして使ってください。

使い方（簡単）:

1. このリポジトリをテンプレートとしてコピーまたはフォークします。
2. リポジトリの Settings → Pages で、公開方法を `GitHub Actions` に変更します。
3. リポジトリの Settings → Environments で `github-pages` を作成します。
4. `site.config.json` を編集して `title`, `description`, `baseUrl` を設定します。
5. 記事は `content/posts/*.md` に Markdown + YAML frontmatter で追加します。
6. 変更を `main` ブランチへ push すると GitHub Actions がビルドし、Actions 経由で GitHub Pages に公開されます。

補足（Pagesの設定）:
- このワークフローは `actions/upload-pages-artifact` + `actions/deploy-pages` を使うため、GitHub Pages の公開ソースを `gh-pages` ブランチに切り替える必要はありません。公開方法は「GitHub Actions」経由になります。
- リポジトリの Settings → Pages で、公開方法が `GitHub Actions` になっていることを確認してください（通常は Actions の最初のデプロイで自動的に反映されます）。
- `baseUrl` はサイトをサブパスでホストする場合に設定してください（例: GitHub Pages のリポジトリページが `https://user.github.io/repo/` の場合は `/repo` を指定するか、空にして相対パスで運用してください）。

カスタマイズ:
- `templates/` のテンプレートを編集して見た目を変えられます。
- `build.js` を改造してタグ、カテゴリ、ページネーション等を追加できます。

カスタムドメイン:
- `CNAME` を `dist/` 直下に含めるか、GitHub リポジトリの Pages 設定でカスタムドメインを登録してください。

注意事項:
- 既に `gh-pages` ブランチを利用している運用がある場合、切り替え前にバックアップを取るか運用方針を検討してください。
- Actions の権限や `secrets.GITHUB_TOKEN` の権限はワークフロー内で設定済みですが、組織ポリシーによっては追加権限が必要になる場合があります。

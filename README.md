# wpversionchecker
wordpress version checker

## Usage

```bash
php wpversionchecker.php
```

- このスクリプトと同じ階層以下を再帰探索します。
- `wp-includes/version.php`（および `wp-include/version.php`）を検出し、WordPress バージョンと対象ディレクトリ階層を表示します。
- 探索中は進捗バーを表示し、完了時に完了メッセージを表示します。

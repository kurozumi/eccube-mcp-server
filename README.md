# EC-CUBE MCP Server

EC-CUBE 4用のMCP（Model Context Protocol）サーバーです。
ClaudeなどのAIアシスタントからEC-CUBEの商品、受注、会員データにアクセスできるようになります。

## 必要要件

- EC-CUBE 4.3以上
- PHP 8.1以上
- Composer

## インストール

### 1. EC-CUBEプロジェクトにクローン

```bash
cd /path/to/ec-cube
git clone https://github.com/kurozumi/eccube-mcp-server.git mcp-server
cd mcp-server
composer install
```

### 2. 環境変数の設定

`ECCUBE_ROOT`環境変数でEC-CUBEのルートディレクトリを指定するか、デフォルトで親の親ディレクトリが使用されます。

## Claude Desktopでの設定

### macOS

`~/Library/Application Support/Claude/claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "eccube": {
      "command": "php",
      "args": ["/path/to/ec-cube/mcp-server/server.php"],
      "env": {
        "ECCUBE_ROOT": "/path/to/ec-cube",
        "APP_ENV": "prod",
        "APP_DEBUG": "0"
      }
    }
  }
}
```

### Windows

`%APPDATA%\Claude\claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "eccube": {
      "command": "php",
      "args": ["C:\\path\\to\\ec-cube\\mcp-server\\server.php"],
      "env": {
        "ECCUBE_ROOT": "C:\\path\\to\\ec-cube",
        "APP_ENV": "prod",
        "APP_DEBUG": "0"
      }
    }
  }
}
```

## Claude Code（CLI）での設定

Claude Code（コマンドラインツール）でこのMCPサーバーを使用する方法です。

### 設定ファイルの種類と配置場所

Claude Codeは複数のスコープで設定を管理できます：

| スコープ | 配置場所 | 用途 | Git共有 |
|---------|---------|------|--------|
| User（ユーザー） | `~/.claude.json` | 全プロジェクト共通の個人設定 | × |
| Project（プロジェクト） | `.mcp.json` | チームで共有する設定 | ○ |
| Local（ローカル） | `~/.claude.json`内のprojects設定 | プロジェクト固有の個人設定 | × |

**優先順位**: Local > Project > User（Localが最優先）

### 方法1: コマンドで設定（推奨）

#### ユーザースコープ（全プロジェクト共通）

```bash
claude mcp add eccube \
  -e ECCUBE_ROOT=/path/to/ec-cube \
  -e APP_ENV=prod \
  -e APP_DEBUG=0 \
  -- php /path/to/ec-cube/mcp-server/server.php
```

#### プロジェクトスコープ（チーム共有）

EC-CUBEプロジェクトのルートディレクトリで実行：

```bash
cd /path/to/ec-cube

claude mcp add eccube --scope project \
  -e ECCUBE_ROOT=. \
  -e APP_ENV=prod \
  -e APP_DEBUG=0 \
  -- php ./mcp-server/server.php
```

これにより `.mcp.json` がプロジェクトルートに作成されます。

### 方法2: 設定ファイルを直接編集

#### ユーザースコープ: `~/.claude.json`

```json
{
  "mcpServers": {
    "eccube": {
      "command": "php",
      "args": ["/path/to/ec-cube/mcp-server/server.php"],
      "env": {
        "ECCUBE_ROOT": "/path/to/ec-cube",
        "APP_ENV": "prod",
        "APP_DEBUG": "0"
      }
    }
  }
}
```

#### プロジェクトスコープ: `.mcp.json`（EC-CUBEルートに配置）

```json
{
  "mcpServers": {
    "eccube": {
      "command": "php",
      "args": ["./mcp-server/server.php"],
      "env": {
        "ECCUBE_ROOT": ".",
        "APP_ENV": "prod",
        "APP_DEBUG": "0"
      }
    }
  }
}
```

### 方法3: プロジェクト固有の個人設定（ローカルスコープ）

チーム共有の設定とは別に、個人用の設定を追加したい場合は `~/.claude.json` 内でプロジェクト単位の設定ができます：

```json
{
  "mcpServers": {
    // ユーザースコープ（全プロジェクト共通）
  },
  "projects": {
    "/path/to/ec-cube": {
      "mcpServers": {
        "eccube": {
          "command": "php",
          "args": ["./mcp-server/server.php"],
          "env": {
            "ECCUBE_ROOT": ".",
            "APP_ENV": "dev",
            "APP_DEBUG": "1"
          }
        }
      }
    }
  }
}
```

### 設定の確認

現在の設定を確認するには：

```bash
claude mcp list
```

### チームでの共有手順

1. **`.mcp.json`をGitに追加**
   ```bash
   git add .mcp.json
   git commit -m "Add Claude Code MCP configuration"
   ```

2. **チームメンバーがクローン後に実行**
   ```bash
   git pull
   cd mcp-server
   composer install
   ```

   `.mcp.json`が自動的に読み込まれます。

### その他のプロジェクト設定ファイル

Claude Codeは `.claude/` ディレクトリ内にも設定ファイルを配置できます：

| ファイル | 用途 | Git共有 |
|---------|------|--------|
| `.claude/settings.json` | 権限、環境変数、ツール設定 | ○ |
| `.claude/settings.local.json` | 個人的なオーバーライド | × (gitignored) |
| `.claude/CLAUDE.md` | プロジェクトのコンテキスト情報 | ○ |

#### `.claude/settings.json` の例

```json
{
  "permissions": {
    "allow": [
      "mcp__eccube__get_product",
      "mcp__eccube__search_products",
      "mcp__eccube__get_order"
    ]
  },
  "env": {
    "APP_ENV": "prod"
  }
}
```

#### `.claude/CLAUDE.md` の例

```markdown
# EC-CUBE プロジェクト

このプロジェクトはEC-CUBE 4を使用したECサイトです。

## MCPサーバーの使い方

- 商品情報の取得: `get_product`, `search_products`
- 受注情報の取得: `get_order`, `search_orders`
- 売上レポート: `get_today_summary`, `get_sales_summary`
```

## 利用可能なツール

### 商品関連

| ツール | 説明 |
|--------|------|
| `get_product` | 商品IDで商品詳細を取得 |
| `search_products` | 商品を検索（キーワード、カテゴリ、在庫状況） |
| `get_out_of_stock_products` | 在庫切れ商品一覧を取得 |

### 受注関連

| ツール | 説明 |
|--------|------|
| `get_order` | 受注IDで受注詳細を取得 |
| `search_orders` | 受注を検索（日付、ステータス、顧客名） |
| `get_today_summary` | 今日の売上サマリーを取得 |
| `get_sales_summary` | 期間別売上サマリーを取得 |

### 会員関連

| ツール | 説明 |
|--------|------|
| `get_customer` | 会員IDで会員詳細を取得 |
| `search_customers` | 会員を検索（名前、メール、電話番号） |
| `get_customer_stats` | 会員統計（総会員数、新規会員数）を取得 |

### システム関連

| ツール | 説明 |
|--------|------|
| `get_system_info` | EC-CUBEのシステム情報を取得 |
| `get_shop_info` | 店舗基本情報を取得 |
| `get_plugins` | プラグイン一覧を取得 |
| `get_categories` | カテゴリ一覧を取得 |

## 使用例

Claude Desktopで以下のような質問ができます：

- 「在庫切れの商品を教えて」
- 「今日の売上状況を見せて」
- 「商品ID:5の詳細情報を取得して」
- 「今週の新規会員数は？」
- 「インストールされているプラグインを一覧で見せて」

## セキュリティに関する注意

- このMCPサーバーはEC-CUBEのデータベースに読み取りアクセスします
- 本番環境での使用は慎重に行ってください
- 信頼できる環境でのみ使用してください
- 必要に応じてアクセス制限を設けてください

## ライセンス

MIT License

## 参考リンク

- [EC-CUBE公式サイト](https://www.ec-cube.net/)
- [Model Context Protocol](https://modelcontextprotocol.io/)
- [MCP PHP SDK](https://github.com/modelcontextprotocol/php-sdk)

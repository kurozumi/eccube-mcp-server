# EC-CUBE MCP Server

[Model Context Protocol (MCP)](https://modelcontextprotocol.io/) server for EC-CUBE. Enables Claude Code to interact with your EC-CUBE store.

## Prerequisites

- Node.js 18+
- EC-CUBE 4.3 with [McpApi plugin](https://github.com/kurozumi/eccube-plugin-mcpapi) installed
- API key created in EC-CUBE admin panel

## Installation

```bash
npm install -g eccube-mcp-server
```

Or use npx (no installation required):

```bash
npx eccube-mcp-server
```

## Setup

### 1. Install McpApi Plugin

Install the [McpApi plugin](https://github.com/kurozumi/eccube-plugin-mcpapi) on your EC-CUBE store.

### 2. Create API Key

1. Login to EC-CUBE admin panel
2. Go to MCP API > API Settings
3. Enter a name and select permissions
4. Click "Register" and copy the generated API key

### 3. Configure Claude Code

Add to `~/.claude/claude_code_config.json`:

```json
{
  "mcpServers": {
    "eccube": {
      "command": "eccube-mcp-server",
      "env": {
        "ECCUBE_BASE_URL": "https://your-shop.com",
        "ECCUBE_API_KEY": "your_api_key"
      }
    }
  }
}
```

Or using npx:

```json
{
  "mcpServers": {
    "eccube": {
      "command": "npx",
      "args": ["eccube-mcp-server"],
      "env": {
        "ECCUBE_BASE_URL": "https://your-shop.com",
        "ECCUBE_API_KEY": "your_api_key"
      }
    }
  }
}
```

### 4. Restart Claude Code

After configuration, restart Claude Code to load the MCP server.

## Available Tools

| Tool | Description | Required Permission |
|------|-------------|---------------------|
| `eccube_list_products` | List products with pagination and filters | product:read |
| `eccube_get_product` | Get product details | product:read |
| `eccube_create_product` | Create a new product | product:write |
| `eccube_update_product` | Update an existing product | product:write |

## Usage Examples (Claude Code)

```
> List all products in EC-CUBE

> Show details for product ID 1

> Create a new product named "Test Product" with price 1000

> Update product ID 1 stock to 50
```

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `ECCUBE_BASE_URL` | Yes | EC-CUBE store URL (e.g., `https://your-shop.com`) |
| `ECCUBE_API_KEY` | Yes | API key from EC-CUBE admin panel |

## Related

- [McpApi Plugin for EC-CUBE](https://github.com/kurozumi/eccube-plugin-mcpapi)
- [Model Context Protocol](https://modelcontextprotocol.io/)
- [EC-CUBE](https://www.ec-cube.net/)

## License

GPL-2.0

## Author

Akira Kurozumi <info@a-zumi.net>

https://a-zumi.net

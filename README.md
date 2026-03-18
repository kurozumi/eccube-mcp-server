# EC-CUBE MCP Server

[Model Context Protocol (MCP)](https://modelcontextprotocol.io/) server for EC-CUBE. Enables Claude Code to interact with your EC-CUBE store.

## Prerequisites

- Node.js 18+
- EC-CUBE 4.3 with [McpApi plugin](https://github.com/kurozumi/eccube-plugin-mcpapi) installed
- API key created in EC-CUBE admin panel

## Setup

### 1. Install McpApi Plugin

Install the [McpApi plugin](https://github.com/kurozumi/eccube-plugin-mcpapi) on your EC-CUBE store.

### 2. Create API Key

1. Login to EC-CUBE admin panel
2. Go to MCP API > API Settings
3. Enter a name and select permissions
4. Click "Register" and copy the generated API key

### 3. Install MCP Server

Clone this repository to your local machine:

```bash
git clone https://github.com/kurozumi/eccube-mcp-server.git
cd eccube-mcp-server
npm install
npm run build
```

### 4. Configure Claude Code

Add to `~/.claude/claude_code_config.json`:

```json
{
  "mcpServers": {
    "eccube": {
      "command": "node",
      "args": ["/path/to/eccube-mcp-server/dist/index.js"],
      "env": {
        "ECCUBE_BASE_URL": "https://your-shop.com",
        "ECCUBE_API_KEY": "your_api_key"
      }
    }
  }
}
```

Replace:
- `/path/to/eccube-mcp-server` with the actual path where you cloned the repository
- `https://your-shop.com` with your EC-CUBE store URL
- `your_api_key` with the API key from step 2

### 5. Restart Claude Code

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

## Architecture

```
Your PC (Local)
├── Claude Code
└── MCP Server (this repository)
        │
        │ HTTP API
        ▼
EC-CUBE Server (Remote)
└── McpApi Plugin
    └── /mcp/api/v1/products
```

The MCP Server runs on your local machine and communicates with your EC-CUBE store via HTTP API.

## Related

- [McpApi Plugin for EC-CUBE](https://github.com/kurozumi/eccube-plugin-mcpapi)
- [Model Context Protocol](https://modelcontextprotocol.io/)
- [EC-CUBE](https://www.ec-cube.net/)

## License

GPL-2.0

## Author

Akira Kurozumi <info@a-zumi.net>

https://a-zumi.net

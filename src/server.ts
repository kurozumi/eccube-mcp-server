import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';
import { EccubeClient } from './client/eccube-client.js';
import { registerProductTools } from './tools/products.js';

export interface ServerConfig {
  baseUrl: string;
  apiKey: string;
}

export async function createServer(config: ServerConfig): Promise<Server> {
  const server = new Server(
    {
      name: 'eccube-mcp-server',
      version: '1.0.0',
    },
    {
      capabilities: {
        tools: {},
      },
    }
  );

  const client = new EccubeClient({
    baseUrl: config.baseUrl,
    apiKey: config.apiKey,
  });

  // Register all tools
  const productTools = registerProductTools(server, client);

  const allTools = {
    ...productTools,
  };

  // List tools handler
  server.setRequestHandler(ListToolsRequestSchema, async () => {
    return {
      tools: Object.entries(allTools).map(([name, tool]) => ({
        name,
        description: tool.description,
        inputSchema: tool.inputSchema,
      })),
    };
  });

  // Call tool handler
  server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;

    const tool = allTools[name as keyof typeof allTools];
    if (!tool) {
      throw new Error(`Unknown tool: ${name}`);
    }

    try {
      return await tool.handler(args as any);
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      return {
        content: [{ type: 'text' as const, text: `Error: ${message}` }],
        isError: true,
      };
    }
  });

  return server;
}

export async function runServer(config: ServerConfig): Promise<void> {
  const server = await createServer(config);
  const transport = new StdioServerTransport();
  await server.connect(transport);

  // Handle shutdown
  process.on('SIGINT', async () => {
    await server.close();
    process.exit(0);
  });
}

import { z } from 'zod';
import type { EccubeClient } from '../client/eccube-client.js';

export const listProductsSchema = z.object({
  page: z.number().optional().describe('Page number (default: 1)'),
  limit: z.number().optional().describe('Items per page (default: 20, max: 100)'),
  status: z.number().optional().describe('Filter by status ID (1=公開, 2=非公開)'),
  name: z.string().optional().describe('Filter by product name (partial match)'),
});

export const getProductSchema = z.object({
  id: z.number().describe('Product ID'),
});

export const createProductSchema = z.object({
  name: z.string().describe('Product name'),
  description_detail: z.string().optional().describe('Detailed description'),
  description_list: z.string().optional().describe('List description'),
  status: z.number().optional().describe('Status ID (1=公開, 2=非公開, default: 2)'),
  price: z.number().optional().describe('Product price'),
  stock: z.number().optional().describe('Stock quantity (if not set, unlimited stock)'),
  category_ids: z.array(z.number()).optional().describe('Category IDs'),
});

export const updateProductSchema = z.object({
  id: z.number().describe('Product ID'),
  name: z.string().optional().describe('Product name'),
  description_detail: z.string().optional().describe('Detailed description'),
  description_list: z.string().optional().describe('List description'),
  status: z.number().optional().describe('Status ID (1=公開, 2=非公開)'),
  price: z.number().optional().describe('Product price'),
  stock: z.number().optional().describe('Stock quantity'),
});

export function registerProductTools(
  server: { setRequestHandler: Function },
  client: EccubeClient
) {
  return {
    eccube_list_products: {
      description: 'List products from EC-CUBE store with pagination and filters',
      inputSchema: {
        type: 'object' as const,
        properties: {
          page: { type: 'number', description: 'Page number (default: 1)' },
          limit: { type: 'number', description: 'Items per page (default: 20, max: 100)' },
          status: { type: 'number', description: 'Filter by status ID (1=公開, 2=非公開)' },
          name: { type: 'string', description: 'Filter by product name (partial match)' },
        },
      },
      handler: async (args: z.infer<typeof listProductsSchema>) => {
        const result = await client.listProducts(args);
        return {
          content: [{ type: 'text' as const, text: JSON.stringify(result, null, 2) }],
        };
      },
    },
    eccube_get_product: {
      description: 'Get detailed information about a specific product',
      inputSchema: {
        type: 'object' as const,
        properties: {
          id: { type: 'number', description: 'Product ID' },
        },
        required: ['id'],
      },
      handler: async (args: z.infer<typeof getProductSchema>) => {
        const result = await client.getProduct(args.id);
        return {
          content: [{ type: 'text' as const, text: JSON.stringify(result, null, 2) }],
        };
      },
    },
    eccube_create_product: {
      description: 'Create a new product in EC-CUBE store',
      inputSchema: {
        type: 'object' as const,
        properties: {
          name: { type: 'string', description: 'Product name' },
          description_detail: { type: 'string', description: 'Detailed description' },
          description_list: { type: 'string', description: 'List description' },
          status: { type: 'number', description: 'Status ID (1=公開, 2=非公開, default: 2)' },
          price: { type: 'number', description: 'Product price' },
          stock: { type: 'number', description: 'Stock quantity (if not set, unlimited stock)' },
          category_ids: {
            type: 'array',
            items: { type: 'number' },
            description: 'Category IDs',
          },
        },
        required: ['name'],
      },
      handler: async (args: z.infer<typeof createProductSchema>) => {
        const result = await client.createProduct(args);
        return {
          content: [{ type: 'text' as const, text: JSON.stringify(result, null, 2) }],
        };
      },
    },
    eccube_update_product: {
      description: 'Update an existing product',
      inputSchema: {
        type: 'object' as const,
        properties: {
          id: { type: 'number', description: 'Product ID' },
          name: { type: 'string', description: 'Product name' },
          description_detail: { type: 'string', description: 'Detailed description' },
          description_list: { type: 'string', description: 'List description' },
          status: { type: 'number', description: 'Status ID (1=公開, 2=非公開)' },
          price: { type: 'number', description: 'Product price' },
          stock: { type: 'number', description: 'Stock quantity' },
        },
        required: ['id'],
      },
      handler: async (args: z.infer<typeof updateProductSchema>) => {
        const { id, ...data } = args;
        const result = await client.updateProduct(id, data);
        return {
          content: [{ type: 'text' as const, text: JSON.stringify(result, null, 2) }],
        };
      },
    },
  };
}

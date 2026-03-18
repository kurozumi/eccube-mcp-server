import type { Product, Order, Customer, PaginatedResponse } from '../types/eccube.js';

export interface EccubeClientConfig {
  baseUrl: string;
  apiKey: string;
}

export class EccubeClient {
  private baseUrl: string;
  private apiKey: string;

  constructor(config: EccubeClientConfig) {
    this.baseUrl = config.baseUrl.replace(/\/$/, '');
    this.apiKey = config.apiKey;
  }

  private async request<T>(
    method: string,
    path: string,
    body?: Record<string, unknown>
  ): Promise<T> {
    const url = `${this.baseUrl}${path}`;
    const headers: Record<string, string> = {
      'X-MCP-API-KEY': this.apiKey,
      'Content-Type': 'application/json',
    };

    const response = await fetch(url, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: response.statusText }));
      throw new Error(`API Error (${response.status}): ${error.message || response.statusText}`);
    }

    return response.json() as Promise<T>;
  }

  // Products
  async listProducts(params?: {
    page?: number;
    limit?: number;
    status?: number;
    name?: string;
  }): Promise<PaginatedResponse<Product>> {
    const query = new URLSearchParams();
    if (params?.page) query.set('page', String(params.page));
    if (params?.limit) query.set('limit', String(params.limit));
    if (params?.status) query.set('status', String(params.status));
    if (params?.name) query.set('name', params.name);

    const path = `/mcp/api/v1/products${query.toString() ? '?' + query.toString() : ''}`;
    return this.request<PaginatedResponse<Product>>('GET', path);
  }

  async getProduct(id: number): Promise<Product> {
    return this.request<Product>('GET', `/mcp/api/v1/products/${id}`);
  }

  async createProduct(data: {
    name: string;
    description_detail?: string;
    description_list?: string;
    status?: number;
    price?: number;
    stock?: number;
    category_ids?: number[];
  }): Promise<Product> {
    return this.request<Product>('POST', '/mcp/api/v1/products', data);
  }

  async updateProduct(
    id: number,
    data: {
      name?: string;
      description_detail?: string;
      description_list?: string;
      status?: number;
      price?: number;
      stock?: number;
    }
  ): Promise<Product> {
    return this.request<Product>('PUT', `/mcp/api/v1/products/${id}`, data);
  }

  // Orders
  async listOrders(params?: {
    page?: number;
    limit?: number;
    status?: number;
    customer_id?: number;
  }): Promise<PaginatedResponse<Order>> {
    const query = new URLSearchParams();
    if (params?.page) query.set('page', String(params.page));
    if (params?.limit) query.set('limit', String(params.limit));
    if (params?.status) query.set('status', String(params.status));
    if (params?.customer_id) query.set('customer_id', String(params.customer_id));

    const path = `/mcp/api/v1/orders${query.toString() ? '?' + query.toString() : ''}`;
    return this.request<PaginatedResponse<Order>>('GET', path);
  }

  async getOrder(id: number): Promise<Order> {
    return this.request<Order>('GET', `/mcp/api/v1/orders/${id}`);
  }

  async updateOrderStatus(id: number, status: number): Promise<Order> {
    return this.request<Order>('PUT', `/mcp/api/v1/orders/${id}/status`, { status });
  }

  // Customers
  async listCustomers(params?: {
    page?: number;
    limit?: number;
    email?: string;
    name?: string;
  }): Promise<PaginatedResponse<Customer>> {
    const query = new URLSearchParams();
    if (params?.page) query.set('page', String(params.page));
    if (params?.limit) query.set('limit', String(params.limit));
    if (params?.email) query.set('email', params.email);
    if (params?.name) query.set('name', params.name);

    const path = `/mcp/api/v1/customers${query.toString() ? '?' + query.toString() : ''}`;
    return this.request<PaginatedResponse<Customer>>('GET', path);
  }

  async getCustomer(id: number): Promise<Customer> {
    return this.request<Customer>('GET', `/mcp/api/v1/customers/${id}`);
  }
}

export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  limit: number;
  pages: number;
}

export interface ProductStatus {
  id: number;
  name: string;
}

export interface Category {
  id: number;
  name: string;
}

export interface ProductClass {
  id: number;
  code: string | null;
  price01: string | null;
  price02: string | null;
  stock_unlimited: boolean;
  stock?: number;
}

export interface Product {
  id: number;
  name: string;
  status: ProductStatus;
  create_date: string;
  update_date: string;
  description_detail?: string;
  description_list?: string;
  search_word?: string;
  note?: string;
  categories?: Category[];
  classes?: ProductClass[];
}

export interface CustomerInfo {
  id: number;
  name: string;
  email: string;
}

export interface OrderStatus {
  id: number;
  name: string;
}

export interface PaymentInfo {
  id: number;
  name: string;
}

export interface OrderItem {
  id: number;
  product_name: string;
  product_code: string | null;
  class_name1: string | null;
  class_name2: string | null;
  price: number;
  quantity: number;
  tax: number;
}

export interface ShippingInfo {
  id: number;
  name: string;
  postal_code: string;
  addr01: string;
  addr02: string;
  delivery: string | null;
  shipping_date: string | null;
  tracking_number: string | null;
}

export interface Order {
  id: number;
  order_no: string;
  status: OrderStatus;
  customer: CustomerInfo | null;
  payment_total: number;
  order_date: string;
  create_date: string;
  update_date: string;
  name?: string;
  email?: string;
  phone_number?: string;
  postal_code?: string;
  addr01?: string;
  addr02?: string;
  subtotal?: number;
  discount?: number;
  delivery_fee_total?: number;
  charge?: number;
  tax?: number;
  total?: number;
  payment?: PaymentInfo;
  items?: OrderItem[];
  shippings?: ShippingInfo[];
}

export interface CustomerStatus {
  id: number;
  name: string;
}

export interface Customer {
  id: number;
  name: string;
  kana: string;
  email: string;
  status: CustomerStatus;
  create_date: string;
  update_date: string;
  name01?: string;
  name02?: string;
  kana01?: string;
  kana02?: string;
  company_name?: string;
  postal_code?: string;
  addr01?: string;
  addr02?: string;
  phone_number?: string;
  birth?: string;
  sex?: string;
  job?: string;
  point?: number;
  note?: string;
  last_buy_date?: string;
  buy_times?: number;
  buy_total?: number;
}

<?php

/*
 * This file is part of EC-CUBE MCP Server
 *
 * Copyright(c) kurozumi All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eccube\MCP\Tools;

use Eccube\Entity\Order;
use Eccube\Repository\OrderRepository;
use Mcp\Attribute\McpTool;
use Mcp\Attribute\ToolParameter;

class OrderTools
{
    public function __construct(
        private readonly OrderRepository $orderRepository
    ) {
    }

    /**
     * 受注IDで受注情報を取得する
     */
    #[McpTool(
        name: 'get_order',
        description: '受注IDを指定して受注の詳細情報を取得します'
    )]
    public function getOrder(
        #[ToolParameter(description: '受注ID', required: true)]
        int $orderId
    ): array {
        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            return ['error' => '受注が見つかりません', 'order_id' => $orderId];
        }

        return $this->formatOrder($order, true);
    }

    /**
     * 受注を検索する
     */
    #[McpTool(
        name: 'search_orders',
        description: '条件を指定して受注を検索します'
    )]
    public function searchOrders(
        #[ToolParameter(description: '検索開始日（YYYY-MM-DD形式）', required: false)]
        ?string $fromDate = null,
        #[ToolParameter(description: '検索終了日（YYYY-MM-DD形式）', required: false)]
        ?string $toDate = null,
        #[ToolParameter(description: '対応状況ID（1:新規受付, 3:キャンセル, 4:対応中, 5:発送済み, 6:入金済み）', required: false)]
        ?int $statusId = null,
        #[ToolParameter(description: '顧客名で検索', required: false)]
        ?string $customerName = null,
        #[ToolParameter(description: '取得件数（デフォルト20件、最大100件）', required: false)]
        int $limit = 20
    ): array {
        $limit = min($limit, 100);

        $qb = $this->orderRepository->createQueryBuilder('o')
            ->select('o')
            ->orderBy('o.order_date', 'DESC')
            ->setMaxResults($limit);

        // 購入処理中を除外
        $qb->andWhere('o.OrderStatus != :processingStatus')
           ->setParameter('processingStatus', 8);

        if ($fromDate) {
            $qb->andWhere('o.order_date >= :fromDate')
               ->setParameter('fromDate', new \DateTime($fromDate.' 00:00:00'));
        }

        if ($toDate) {
            $qb->andWhere('o.order_date <= :toDate')
               ->setParameter('toDate', new \DateTime($toDate.' 23:59:59'));
        }

        if ($statusId) {
            $qb->andWhere('o.OrderStatus = :status')
               ->setParameter('status', $statusId);
        }

        if ($customerName) {
            $qb->andWhere('CONCAT(o.name01, o.name02) LIKE :name')
               ->setParameter('name', '%'.$customerName.'%');
        }

        $orders = $qb->getQuery()->getResult();
        $results = [];

        foreach ($orders as $order) {
            $results[] = $this->formatOrder($order, false);
        }

        return [
            'count' => count($results),
            'orders' => $results,
        ];
    }

    /**
     * 今日の売上サマリーを取得する
     */
    #[McpTool(
        name: 'get_today_summary',
        description: '今日の売上サマリー（受注件数、売上合計）を取得します'
    )]
    public function getTodaySummary(): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        $qb = $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id) as order_count, SUM(o.payment_total) as total_sales')
            ->where('o.order_date >= :today')
            ->andWhere('o.order_date < :tomorrow')
            ->andWhere('o.OrderStatus NOT IN (:excludeStatus)')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('excludeStatus', [3, 8]); // キャンセル、購入処理中を除外

        $result = $qb->getQuery()->getSingleResult();

        return [
            'date' => $today->format('Y-m-d'),
            'order_count' => (int) $result['order_count'],
            'total_sales' => (int) ($result['total_sales'] ?? 0),
        ];
    }

    /**
     * 期間別売上サマリーを取得する
     */
    #[McpTool(
        name: 'get_sales_summary',
        description: '指定期間の売上サマリーを取得します'
    )]
    public function getSalesSummary(
        #[ToolParameter(description: '開始日（YYYY-MM-DD形式）', required: true)]
        string $fromDate,
        #[ToolParameter(description: '終了日（YYYY-MM-DD形式）', required: true)]
        string $toDate
    ): array {
        $from = new \DateTime($fromDate.' 00:00:00');
        $to = new \DateTime($toDate.' 23:59:59');

        $qb = $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id) as order_count, SUM(o.payment_total) as total_sales, AVG(o.payment_total) as avg_sales')
            ->where('o.order_date >= :from')
            ->andWhere('o.order_date <= :to')
            ->andWhere('o.OrderStatus NOT IN (:excludeStatus)')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('excludeStatus', [3, 8]);

        $result = $qb->getQuery()->getSingleResult();

        // ステータス別集計
        $statusQb = $this->orderRepository->createQueryBuilder('o')
            ->select('IDENTITY(o.OrderStatus) as status_id, COUNT(o.id) as count')
            ->where('o.order_date >= :from')
            ->andWhere('o.order_date <= :to')
            ->andWhere('o.OrderStatus NOT IN (:excludeStatus)')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('excludeStatus', [8])
            ->groupBy('o.OrderStatus');

        $statusResults = $statusQb->getQuery()->getResult();
        $byStatus = [];
        foreach ($statusResults as $sr) {
            $byStatus[$sr['status_id']] = (int) $sr['count'];
        }

        return [
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'order_count' => (int) $result['order_count'],
            'total_sales' => (int) ($result['total_sales'] ?? 0),
            'average_sales' => (int) ($result['avg_sales'] ?? 0),
            'by_status' => $byStatus,
        ];
    }

    private function formatOrder(Order $order, bool $detailed): array
    {
        $data = [
            'id' => $order->getId(),
            'order_no' => $order->getOrderNo(),
            'customer_name' => $order->getName01().' '.$order->getName02(),
            'email' => $order->getEmail(),
            'phone_number' => $order->getPhoneNumber(),
            'subtotal' => $order->getSubtotal(),
            'discount' => $order->getDiscount(),
            'delivery_fee_total' => $order->getDeliveryFeeTotal(),
            'charge' => $order->getCharge(),
            'tax' => $order->getTax(),
            'total' => $order->getTotal(),
            'payment_total' => $order->getPaymentTotal(),
            'status' => $order->getOrderStatus()->getName(),
            'status_id' => $order->getOrderStatus()->getId(),
            'payment_method' => $order->getPaymentMethod(),
            'order_date' => $order->getOrderDate()?->format('Y-m-d H:i:s'),
        ];

        if ($detailed) {
            $data['note'] = $order->getNote();
            $data['message'] = $order->getMessage();
            $data['payment_date'] = $order->getPaymentDate()?->format('Y-m-d H:i:s');
            $data['create_date'] = $order->getCreateDate()?->format('Y-m-d H:i:s');
            $data['update_date'] = $order->getUpdateDate()?->format('Y-m-d H:i:s');

            // 配送先情報
            $data['pref'] = $order->getPref()?->getName();
            $data['addr01'] = $order->getAddr01();
            $data['addr02'] = $order->getAddr02();
            $data['postal_code'] = $order->getPostalCode();

            // 受注明細
            $items = [];
            foreach ($order->getOrderItems() as $item) {
                if (!$item->isProduct()) {
                    continue;
                }
                $items[] = [
                    'product_name' => $item->getProductName(),
                    'product_code' => $item->getProductCode(),
                    'class_category_name1' => $item->getClassCategoryName1(),
                    'class_category_name2' => $item->getClassCategoryName2(),
                    'price' => $item->getPrice(),
                    'quantity' => $item->getQuantity(),
                    'tax_rate' => $item->getTaxRate(),
                ];
            }
            $data['order_items'] = $items;

            // 出荷情報
            $shippings = [];
            foreach ($order->getShippings() as $shipping) {
                $shippings[] = [
                    'id' => $shipping->getId(),
                    'name' => $shipping->getName01().' '.$shipping->getName02(),
                    'shipping_date' => $shipping->getShippingDate()?->format('Y-m-d'),
                    'tracking_number' => $shipping->getTrackingNumber(),
                ];
            }
            $data['shippings'] = $shippings;
        }

        return $data;
    }
}

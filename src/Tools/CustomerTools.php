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

use Eccube\Entity\Customer;
use Eccube\Repository\CustomerRepository;
use Mcp\Attribute\McpTool;
use Mcp\Attribute\ToolParameter;

class CustomerTools
{
    public function __construct(
        private readonly CustomerRepository $customerRepository
    ) {
    }

    /**
     * 会員IDで会員情報を取得する
     */
    #[McpTool(
        name: 'get_customer',
        description: '会員IDを指定して会員の詳細情報を取得します'
    )]
    public function getCustomer(
        #[ToolParameter(description: '会員ID', required: true)]
        int $customerId
    ): array {
        $customer = $this->customerRepository->find($customerId);

        if (!$customer) {
            return ['error' => '会員が見つかりません', 'customer_id' => $customerId];
        }

        return $this->formatCustomer($customer, true);
    }

    /**
     * 会員を検索する
     */
    #[McpTool(
        name: 'search_customers',
        description: '条件を指定して会員を検索します'
    )]
    public function searchCustomers(
        #[ToolParameter(description: '名前で検索', required: false)]
        ?string $name = null,
        #[ToolParameter(description: 'メールアドレスで検索', required: false)]
        ?string $email = null,
        #[ToolParameter(description: '電話番号で検索', required: false)]
        ?string $phoneNumber = null,
        #[ToolParameter(description: '取得件数（デフォルト20件、最大100件）', required: false)]
        int $limit = 20
    ): array {
        $limit = min($limit, 100);

        $qb = $this->customerRepository->createQueryBuilder('c')
            ->select('c')
            ->where('c.Status = :status')
            ->setParameter('status', 2) // 本会員のみ
            ->orderBy('c.id', 'DESC')
            ->setMaxResults($limit);

        if ($name) {
            $qb->andWhere('CONCAT(c.name01, c.name02) LIKE :name')
               ->setParameter('name', '%'.$name.'%');
        }

        if ($email) {
            $qb->andWhere('c.email LIKE :email')
               ->setParameter('email', '%'.$email.'%');
        }

        if ($phoneNumber) {
            $qb->andWhere('c.phone_number LIKE :phone')
               ->setParameter('phone', '%'.$phoneNumber.'%');
        }

        $customers = $qb->getQuery()->getResult();
        $results = [];

        foreach ($customers as $customer) {
            $results[] = $this->formatCustomer($customer, false);
        }

        return [
            'count' => count($results),
            'customers' => $results,
        ];
    }

    /**
     * 会員統計を取得する
     */
    #[McpTool(
        name: 'get_customer_stats',
        description: '会員の統計情報（総会員数、新規会員数等）を取得します'
    )]
    public function getCustomerStats(
        #[ToolParameter(description: '期間（today, week, month, year）', required: false)]
        string $period = 'month'
    ): array {
        // 総会員数
        $totalQb = $this->customerRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.Status = :status')
            ->setParameter('status', 2);
        $totalCount = (int) $totalQb->getQuery()->getSingleScalarResult();

        // 期間の設定
        $now = new \DateTime();
        switch ($period) {
            case 'today':
                $from = new \DateTime('today');
                break;
            case 'week':
                $from = new \DateTime('-7 days');
                break;
            case 'year':
                $from = new \DateTime('-1 year');
                break;
            case 'month':
            default:
                $from = new \DateTime('-1 month');
                break;
        }

        // 新規会員数
        $newQb = $this->customerRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.Status = :status')
            ->andWhere('c.create_date >= :from')
            ->setParameter('status', 2)
            ->setParameter('from', $from);
        $newCount = (int) $newQb->getQuery()->getSingleScalarResult();

        return [
            'period' => $period,
            'total_customers' => $totalCount,
            'new_customers' => $newCount,
            'from_date' => $from->format('Y-m-d'),
        ];
    }

    private function formatCustomer(Customer $customer, bool $detailed): array
    {
        $data = [
            'id' => $customer->getId(),
            'name' => $customer->getName01().' '.$customer->getName02(),
            'kana' => $customer->getKana01().' '.$customer->getKana02(),
            'email' => $customer->getEmail(),
            'phone_number' => $customer->getPhoneNumber(),
            'status' => $customer->getStatus()->getName(),
            'create_date' => $customer->getCreateDate()?->format('Y-m-d H:i:s'),
        ];

        if ($detailed) {
            $data['company_name'] = $customer->getCompanyName();
            $data['postal_code'] = $customer->getPostalCode();
            $data['pref'] = $customer->getPref()?->getName();
            $data['addr01'] = $customer->getAddr01();
            $data['addr02'] = $customer->getAddr02();
            $data['sex'] = $customer->getSex()?->getName();
            $data['birth'] = $customer->getBirth()?->format('Y-m-d');
            $data['job'] = $customer->getJob()?->getName();
            $data['point'] = $customer->getPoint();
            $data['note'] = $customer->getNote();
            $data['first_buy_date'] = $customer->getFirstBuyDate()?->format('Y-m-d');
            $data['last_buy_date'] = $customer->getLastBuyDate()?->format('Y-m-d');
            $data['buy_times'] = $customer->getBuyTimes();
            $data['buy_total'] = $customer->getBuyTotal();
            $data['update_date'] = $customer->getUpdateDate()?->format('Y-m-d H:i:s');
        }

        return $data;
    }
}

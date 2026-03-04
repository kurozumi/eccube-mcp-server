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

use Eccube\Entity\Product;
use Eccube\Repository\ProductRepository;
use Mcp\Attribute\McpTool;
use Mcp\Attribute\ToolParameter;

class ProductTools
{
    public function __construct(
        private readonly ProductRepository $productRepository
    ) {
    }

    /**
     * 商品IDで商品情報を取得する
     */
    #[McpTool(
        name: 'get_product',
        description: '商品IDを指定して商品の詳細情報を取得します'
    )]
    public function getProduct(
        #[ToolParameter(description: '商品ID', required: true)]
        int $productId
    ): array {
        $product = $this->productRepository->find($productId);

        if (!$product) {
            return ['error' => '商品が見つかりません', 'product_id' => $productId];
        }

        return $this->formatProduct($product, true);
    }

    /**
     * 商品を検索する
     */
    #[McpTool(
        name: 'search_products',
        description: '条件を指定して商品を検索します'
    )]
    public function searchProducts(
        #[ToolParameter(description: '検索キーワード（商品名）', required: false)]
        ?string $keyword = null,
        #[ToolParameter(description: 'カテゴリID', required: false)]
        ?int $categoryId = null,
        #[ToolParameter(description: '在庫切れのみ取得するか', required: false)]
        bool $outOfStock = false,
        #[ToolParameter(description: '取得件数（デフォルト10件、最大100件）', required: false)]
        int $limit = 10
    ): array {
        $limit = min($limit, 100);

        $qb = $this->productRepository->createQueryBuilder('p')
            ->select('p')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit);

        if ($keyword) {
            $qb->andWhere('p.name LIKE :keyword')
               ->setParameter('keyword', '%'.$keyword.'%');
        }

        if ($categoryId) {
            $qb->innerJoin('p.ProductCategories', 'pc')
               ->andWhere('pc.Category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        $products = $qb->getQuery()->getResult();
        $results = [];

        foreach ($products as $product) {
            $formatted = $this->formatProduct($product, false);

            if ($outOfStock && $formatted['stock_total'] > 0) {
                continue;
            }

            $results[] = $formatted;
        }

        return [
            'count' => count($results),
            'products' => $results,
        ];
    }

    /**
     * 在庫切れ商品を取得する
     */
    #[McpTool(
        name: 'get_out_of_stock_products',
        description: '在庫切れ（在庫数0）の商品一覧を取得します'
    )]
    public function getOutOfStockProducts(
        #[ToolParameter(description: '取得件数（デフォルト20件、最大100件）', required: false)]
        int $limit = 20
    ): array {
        $limit = min($limit, 100);

        $qb = $this->productRepository->createQueryBuilder('p')
            ->select('p')
            ->innerJoin('p.ProductClasses', 'pc')
            ->where('pc.visible = :visible')
            ->andWhere('pc.stock = 0 OR pc.stock IS NULL')
            ->andWhere('pc.stock_unlimited = :stockUnlimited')
            ->setParameter('visible', true)
            ->setParameter('stockUnlimited', false)
            ->groupBy('p.id')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit);

        $products = $qb->getQuery()->getResult();
        $results = [];

        foreach ($products as $product) {
            $results[] = $this->formatProduct($product, false);
        }

        return [
            'count' => count($results),
            'products' => $results,
        ];
    }

    private function formatProduct(Product $product, bool $detailed): array
    {
        $stockTotal = 0;
        $priceMin = null;
        $priceMax = null;

        foreach ($product->getProductClasses() as $pc) {
            if (!$pc->isVisible()) {
                continue;
            }

            $price = $pc->getPrice02();
            if ($price !== null) {
                if ($priceMin === null || $price < $priceMin) {
                    $priceMin = $price;
                }
                if ($priceMax === null || $price > $priceMax) {
                    $priceMax = $price;
                }
            }

            if (!$pc->isStockUnlimited() && $pc->getStock() !== null) {
                $stockTotal += $pc->getStock();
            }
        }

        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'stock_total' => $stockTotal,
            'status' => $product->getStatus()->getName(),
        ];

        if ($detailed) {
            $data['description_list'] = $product->getDescriptionList();
            $data['description_detail'] = $product->getDescriptionDetail();
            $data['search_word'] = $product->getSearchWord();
            $data['free_area'] = $product->getFreeArea();
            $data['create_date'] = $product->getCreateDate()?->format('Y-m-d H:i:s');
            $data['update_date'] = $product->getUpdateDate()?->format('Y-m-d H:i:s');

            // カテゴリ情報
            $categories = [];
            foreach ($product->getProductCategories() as $pc) {
                $categories[] = [
                    'id' => $pc->getCategory()->getId(),
                    'name' => $pc->getCategory()->getName(),
                ];
            }
            $data['categories'] = $categories;

            // 規格情報
            $classes = [];
            foreach ($product->getProductClasses() as $pc) {
                if (!$pc->isVisible()) {
                    continue;
                }
                $class = [
                    'id' => $pc->getId(),
                    'code' => $pc->getCode(),
                    'price01' => $pc->getPrice01(),
                    'price02' => $pc->getPrice02(),
                    'stock' => $pc->isStockUnlimited() ? '無制限' : $pc->getStock(),
                ];
                if ($pc->getClassCategory1()) {
                    $class['class_category1'] = $pc->getClassCategory1()->getName();
                }
                if ($pc->getClassCategory2()) {
                    $class['class_category2'] = $pc->getClassCategory2()->getName();
                }
                $classes[] = $class;
            }
            $data['product_classes'] = $classes;
        }

        return $data;
    }
}

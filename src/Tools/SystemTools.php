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

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\Constant;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Repository\CategoryRepository;
use Mcp\Attribute\McpTool;
use Mcp\Attribute\ToolParameter;

class SystemTools
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BaseInfoRepository $baseInfoRepository,
        private readonly PluginRepository $pluginRepository,
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    /**
     * EC-CUBEのシステム情報を取得する
     */
    #[McpTool(
        name: 'get_system_info',
        description: 'EC-CUBEのバージョン、PHP情報、データベース情報などのシステム情報を取得します'
    )]
    public function getSystemInfo(): array
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        return [
            'eccube_version' => Constant::VERSION,
            'php_version' => PHP_VERSION,
            'database' => [
                'platform' => $platform::class,
                'server_version' => $connection->getServerVersion(),
            ],
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'intl' => extension_loaded('intl'),
                'mbstring' => extension_loaded('mbstring'),
                'curl' => extension_loaded('curl'),
                'openssl' => extension_loaded('openssl'),
                'zip' => extension_loaded('zip'),
                'gd' => extension_loaded('gd'),
            ],
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }

    /**
     * 店舗情報を取得する
     */
    #[McpTool(
        name: 'get_shop_info',
        description: '店舗の基本情報（店舗名、住所、連絡先等）を取得します'
    )]
    public function getShopInfo(): array
    {
        $baseInfo = $this->baseInfoRepository->get();

        return [
            'shop_name' => $baseInfo->getShopName(),
            'company_name' => $baseInfo->getCompanyName(),
            'postal_code' => $baseInfo->getPostalCode(),
            'pref' => $baseInfo->getPref()?->getName(),
            'addr01' => $baseInfo->getAddr01(),
            'addr02' => $baseInfo->getAddr02(),
            'phone_number' => $baseInfo->getPhoneNumber(),
            'business_hour' => $baseInfo->getBusinessHour(),
            'email01' => $baseInfo->getEmail01(),
            'email02' => $baseInfo->getEmail02(),
            'email03' => $baseInfo->getEmail03(),
            'email04' => $baseInfo->getEmail04(),
            'good_traded' => $baseInfo->getGoodTraded(),
            'message' => $baseInfo->getMessage(),
            'option_point' => $baseInfo->isOptionPoint(),
            'option_delivery_fee' => $baseInfo->isOptionDeliveryFee(),
            'option_multiple_shipping' => $baseInfo->isOptionMultipleShipping(),
            'option_mypage_order_status_display' => $baseInfo->isOptionMypageOrderStatusDisplay(),
            'option_nostock_hidden' => $baseInfo->isOptionNostockHidden(),
            'option_favorite_product' => $baseInfo->isOptionFavoriteProduct(),
        ];
    }

    /**
     * インストール済みプラグイン一覧を取得する
     */
    #[McpTool(
        name: 'get_plugins',
        description: 'インストール済みのプラグイン一覧を取得します'
    )]
    public function getPlugins(
        #[ToolParameter(description: '有効なプラグインのみ取得するか', required: false)]
        bool $enabledOnly = false
    ): array {
        $qb = $this->pluginRepository->createQueryBuilder('p')
            ->select('p')
            ->orderBy('p.id', 'ASC');

        if ($enabledOnly) {
            $qb->andWhere('p.enabled = :enabled')
               ->setParameter('enabled', true);
        }

        $plugins = $qb->getQuery()->getResult();
        $results = [];

        foreach ($plugins as $plugin) {
            $results[] = [
                'id' => $plugin->getId(),
                'name' => $plugin->getName(),
                'code' => $plugin->getCode(),
                'version' => $plugin->getVersion(),
                'enabled' => $plugin->isEnabled(),
                'initialized' => $plugin->isInitialized(),
            ];
        }

        return [
            'count' => count($results),
            'plugins' => $results,
        ];
    }

    /**
     * カテゴリ一覧を取得する
     */
    #[McpTool(
        name: 'get_categories',
        description: 'カテゴリの階層構造を取得します'
    )]
    public function getCategories(): array
    {
        $categories = $this->categoryRepository->getList();
        $results = [];

        foreach ($categories as $category) {
            $results[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'hierarchy' => $category->getHierarchy(),
                'parent_id' => $category->getParent()?->getId(),
            ];
        }

        return [
            'count' => count($results),
            'categories' => $results,
        ];
    }
}

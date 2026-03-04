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

require_once __DIR__.'/vendor/autoload.php';

use Eccube\MCP\Tools\ProductTools;
use Eccube\MCP\Tools\OrderTools;
use Eccube\MCP\Tools\CustomerTools;
use Eccube\MCP\Tools\SystemTools;
use Mcp\Server\Server;
use Mcp\Transport\StdioTransport;

// EC-CUBEのコンテナを取得
$container = require_once __DIR__.'/bootstrap.php';

// リポジトリの取得
$entityManager = $container->get(\Doctrine\ORM\EntityManagerInterface::class);
$productRepository = $container->get(\Eccube\Repository\ProductRepository::class);
$orderRepository = $container->get(\Eccube\Repository\OrderRepository::class);
$customerRepository = $container->get(\Eccube\Repository\CustomerRepository::class);
$baseInfoRepository = $container->get(\Eccube\Repository\BaseInfoRepository::class);
$pluginRepository = $container->get(\Eccube\Repository\PluginRepository::class);
$categoryRepository = $container->get(\Eccube\Repository\CategoryRepository::class);

// ツールのインスタンス化
$productTools = new ProductTools($productRepository);
$orderTools = new OrderTools($orderRepository);
$customerTools = new CustomerTools($customerRepository);
$systemTools = new SystemTools(
    $entityManager,
    $baseInfoRepository,
    $pluginRepository,
    $categoryRepository
);

// MCPサーバーの構築
$server = Server::builder()
    ->setServerInfo('EC-CUBE MCP Server', '1.0.0')
    ->registerToolsFromObject($productTools)
    ->registerToolsFromObject($orderTools)
    ->registerToolsFromObject($customerTools)
    ->registerToolsFromObject($systemTools)
    ->build();

// STDIOトランスポートで実行
$transport = new StdioTransport();
$server->run($transport);

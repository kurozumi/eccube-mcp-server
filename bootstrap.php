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

use Eccube\Kernel;
use Symfony\Component\Dotenv\Dotenv;

// EC-CUBEのルートディレクトリを環境変数から取得、またはデフォルト値を使用
$eccubeRoot = $_SERVER['ECCUBE_ROOT'] ?? dirname(__DIR__, 2);

if (!file_exists($eccubeRoot.'/vendor/autoload.php')) {
    throw new RuntimeException(
        'EC-CUBE root directory not found. Please set ECCUBE_ROOT environment variable.'
    );
}

require_once $eccubeRoot.'/vendor/autoload.php';

// 環境変数の読み込み
if (file_exists($eccubeRoot.'/.env')) {
    $dotenv = new Dotenv();
    $dotenv->loadEnv($eccubeRoot.'/.env');
}

$env = $_SERVER['APP_ENV'] ?? 'prod';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? false);

// EC-CUBEカーネルの起動
$kernel = new Kernel($env, $debug);
$kernel->boot();

return $kernel->getContainer();

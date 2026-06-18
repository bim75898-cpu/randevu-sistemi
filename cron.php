<?php
/**
 * Cron entry point - call this daily via task scheduler:
 *   php C:\xampp\htdocs\randv\cron.php
 */
define('CRON_RUNNING', true);

require_once __DIR__ . '/config/database.php';

require_once __DIR__ . '/lib/BackupService.php';
BackupService::runAuto();

require_once __DIR__ . '/lib/CacheService.php';
$cache = CacheService::init();
$cache->cleanup();

echo "[" . date('Y-m-d H:i:s') . "] Cron completed.\n";
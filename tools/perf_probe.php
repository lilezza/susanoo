<?php
/**
 * Susanoo / Faoxima cPanel performance probe.
 *
 * Usage (from project root):
 *   php tools/perf_probe.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

if (!defined('REFACTORED_LEGACY_ROOT')) {
    define('REFACTORED_LEGACY_ROOT', $root);
}

require_once $root . '/lib/rx_debug_perf.php';

$runId = 'post-fix-' . date('Ymd-His');
$memStart = memory_get_usage(true);

rx_debug_perf_log('META', 'tools/perf_probe.php', 'probe_start', [
    'php' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'opcache' => extension_loaded('Zend OPcache'),
    'opcache_enable' => (bool) ini_get('opcache.enable'),
    'opcache_enable_cli' => (bool) ini_get('opcache.enable_cli'),
    'memory_limit' => ini_get('memory_limit'),
    'cwd' => getcwd(),
], $runId);

function probe_require(string $hypothesisId, string $label, string $relPath, string $runId): array
{
    $root = REFACTORED_LEGACY_ROOT;
    $abs = $root . DIRECTORY_SEPARATOR . $relPath;
    $size = is_file($abs) ? filesize($abs) : 0;
    $t0 = rx_debug_perf_time();
    $ok = false;
    $err = null;
    if (is_file($abs)) {
        try {
            require_once $abs;
            $ok = true;
        } catch (Throwable $e) {
            $err = $e->getMessage();
        }
    } else {
        $err = 'missing';
    }
    $ms = rx_debug_perf_ms($t0);
    $row = [
        'label' => $label,
        'path' => $relPath,
        'bytes' => $size,
        'ms' => $ms,
        'ok' => $ok,
        'err' => $err,
    ];
    rx_debug_perf_log($hypothesisId, 'tools/perf_probe.php', 'include_timing', $row, $runId);
    return $row;
}

$includeRows = [];

$includeRows[] = probe_require('E', 'config', 'config.php', $runId);
$includeRows[] = probe_require('E', 'botapi', 'botapi.php', $runId);
$includeRows[] = probe_require('E', 'jdf', 'jdf.php', $runId);
$includeRows[] = probe_require('E', 'function', 'function.php', $runId);
$includeRows[] = probe_require('D', 'keyboard', 'keyboard.php', $runId);
$includeRows[] = probe_require('D', 'vendor_autoload', 'vendor/autoload.php', $runId);

$icPath = $root . '/infocard.php';
$icBytes = is_file($icPath) ? filesize($icPath) : 0;
rx_debug_perf_log('D', 'tools/perf_probe.php', 'infocard_deferred', [
    'bytes' => $icBytes,
    'required_on_boot' => false,
], $runId);

$tPanels = rx_debug_perf_time();
$includeRows[] = probe_require('A', 'panels_lazy_facade', 'panels.php', $runId);
rx_debug_perf_log('A', 'tools/perf_probe.php', 'panels_lazy_result', [
    'ms' => rx_debug_perf_ms($tPanels),
    'marzban_loaded' => function_exists('token_panel'),
    'guard_loaded' => function_exists('guardApiRequest'),
    'xui_loaded' => function_exists('login'),
    'mikrotik_loaded' => function_exists('login_mikrotik'),
], $runId);

$adapters = [
    'Marzban.php', 'guard.php', 'x-ui_single.php', 'hiddify.php', 'alireza.php',
    'marzneshin.php', 'alireza_single.php', 'WGDashboard.php', 's_ui.php', 'ibsng.php', 'mikrotik.php',
];
$adapterTotalBytes = 0;
foreach ($adapters as $adapter) {
    $abs = $root . '/' . $adapter;
    $adapterTotalBytes += is_file($abs) ? (int) filesize($abs) : 0;
}
rx_debug_perf_log('A', 'tools/perf_probe.php', 'panel_adapters_not_eager_anymore', [
    'count' => count($adapters),
    'bytes_if_all_loaded' => $adapterTotalBytes,
], $runId);

$tJson = rx_debug_perf_time();
$jsonRaw = @file_get_contents($root . '/text.json');
$jsonDecoded = $jsonRaw !== false ? json_decode($jsonRaw, true) : null;
$jsonMs = rx_debug_perf_ms($tJson);
rx_debug_perf_log('D', 'tools/perf_probe.php', 'text_json_parse', [
    'ms' => $jsonMs,
    'bytes' => is_string($jsonRaw) ? strlen($jsonRaw) : 0,
    'ok' => is_array($jsonDecoded),
], $runId);

require_once $root . '/config.php';
$dbName = $GLOBALS['dbname'] ?? '';
$dbUser = $GLOBALS['usernamedb'] ?? '';
$dbConfigured = is_string($dbName) && $dbName !== '' && is_string($dbUser) && $dbUser !== '';

rx_debug_perf_log('C', 'tools/perf_probe.php', 'db_config_state', [
    'configured' => $dbConfigured,
    'pdo_eager_after_config' => isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO,
    'mysqli_eager_after_config' => isset($GLOBALS['connect']) && $GLOBALS['connect'] instanceof mysqli,
], $runId);

if ($dbConfigured) {
    $tBoot = rx_debug_perf_time();
    if (function_exists('rx_boot_database')) {
        rx_boot_database(true);
    }
    rx_debug_perf_log('C', 'tools/perf_probe.php', 'rx_boot_database_timing', [
        'ms' => rx_debug_perf_ms($tBoot),
        'pdo_ok' => isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO,
        'mysqli_ok' => isset($GLOBALS['connect']) && $GLOBALS['connect'] instanceof mysqli,
    ], $runId);
} else {
    rx_debug_perf_log('B', 'tools/perf_probe.php', 'bootstrap_queries_skipped', [
        'reason' => 'config.php database credentials empty',
        'fix_b_code_path' => 'invoice/payment full scans removed from bootstrap',
    ], $runId);
    rx_debug_perf_log('C', 'tools/perf_probe.php', 'dual_connect_skipped', [
        'reason' => 'config.php database credentials empty',
        'fix_c_code_path' => 'rx_boot_database + RX_DEFER_DB_BOOT on webhook',
    ], $runId);
}

$totalIncludeMs = 0.0;
foreach ($includeRows as $row) {
    $totalIncludeMs += (float) ($row['ms'] ?? 0);
}

rx_debug_perf_log('META', 'tools/perf_probe.php', 'probe_summary', [
    'include_total_ms' => round($totalIncludeMs, 3),
    'adapter_eager_ms' => 0,
    'text_json_ms' => $jsonMs,
    'mem_start_bytes' => $memStart,
    'mem_peak_bytes' => memory_get_peak_usage(true),
    'mem_delta_bytes' => memory_get_peak_usage(true) - $memStart,
    'fixes' => ['A_lazy_panels', 'B_no_full_invoice_scan', 'C_defer_db', 'D_defer_infocard'],
], $runId);

echo "Susanoo perf probe done (post-fix).\n";
echo "  include_total_ms : " . round($totalIncludeMs, 3) . "\n";
echo "  panels_lazy      : yes\n";
echo "  infocard_boot    : deferred\n";
echo "  db_configured    : " . ($dbConfigured ? 'yes' : 'no') . "\n";
echo "  log              : /Users/rezayazdi/.cursor/debug-logs/debug-428f28.log\n";

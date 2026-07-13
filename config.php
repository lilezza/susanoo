<?php

$dbname     = '';
$usernamedb = '';
$passworddb = '';


$connect = null;
$pdo     = null;
$dsn     = '';
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
];

/**
 * Open PDO (+ optional mysqli) once. Safe to call repeatedly.
 * Webhook sets RX_DEFER_DB_BOOT so unauthorized Telegram IP probes skip DB entirely.
 */
if (!function_exists('rx_boot_database')) {
    function rx_boot_database(bool $withMysqli = true): void
    {
        static $pdoBooted = false;
        static $mysqliBooted = false;

        $dbname = $GLOBALS['dbname'] ?? '';
        $usernamedb = $GLOBALS['usernamedb'] ?? '';
        $passworddb = $GLOBALS['passworddb'] ?? '';
        $options = $GLOBALS['options'] ?? [];

        if (!is_string($dbname) || $dbname === '' || !is_string($usernamedb) || $usernamedb === '') {
            return;
        }

        if (!$pdoBooted) {
            $pdoBooted = true;
            $dsn = 'mysql:host=localhost;dbname=' . $dbname . ';charset=utf8mb4';
            $GLOBALS['dsn'] = $dsn;
            try {
                $pdo = new PDO($dsn, $usernamedb, $passworddb, is_array($options) ? $options : []);
                $GLOBALS['pdo'] = $pdo;
            } catch (\PDOException $rxPdoError) {
                $GLOBALS['pdo'] = null;
                error_log('config.php PDO connection failed: ' . $rxPdoError->getMessage());
            }
        }

        if ($withMysqli && !$mysqliBooted) {
            $mysqliBooted = true;
            if (function_exists('mysqli_report')) {
                @mysqli_report(MYSQLI_REPORT_OFF);
            }
            try {
                $connect = @mysqli_connect('localhost', $usernamedb, $passworddb, $dbname);
            } catch (\Throwable $rxMysqliConnectError) {
                $connect = null;
                error_log('config.php mysqli_connect failed: ' . $rxMysqliConnectError->getMessage());
            }
            if ($connect instanceof mysqli) {
                @mysqli_set_charset($connect, 'utf8mb4');
                $GLOBALS['connect'] = $connect;
            } else {
                $GLOBALS['connect'] = null;
            }
            // Keep legacy local var in sync when config.php is included at top-level.
            if (array_key_exists('connect', $GLOBALS)) {
                // no-op; callers use $GLOBALS or global $connect after extract below
            }
        }
    }
}

if ($dbname !== '' && $usernamedb !== '') {
    $dsn = 'mysql:host=localhost;dbname=' . $dbname . ';charset=utf8mb4';
    if (!defined('RX_DEFER_DB_BOOT') || RX_DEFER_DB_BOOT !== true) {
        rx_boot_database(true);
        $pdo = $GLOBALS['pdo'] ?? null;
        $connect = $GLOBALS['connect'] ?? null;
    }
} else {
    $rxInstallerPending = is_file(__DIR__ . DIRECTORY_SEPARATOR . 'installer' . DIRECTORY_SEPARATOR . 'index.php');
    if (!$rxInstallerPending) {
        $rxConfigEmptyMarker = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rx_config_empty.flag';
        if (!is_file($rxConfigEmptyMarker) || (time() - (int) @filemtime($rxConfigEmptyMarker)) > 3600) {
            error_log('config.php: database credentials are empty — fill $dbname/$usernamedb/$passworddb to enable DB-backed features.');
            @touch($rxConfigEmptyMarker);
        }
        unset($rxConfigEmptyMarker);
    }
    unset($rxInstallerPending);
}

$APIKEY                     = '';
$adminnumber                = '';
$domainhosts                = '';
$usernamebot                = '';
$telegramCurlTimeout        = 10;
$telegramStrictIpValidation = true;
$domainhosts                = rtrim(preg_replace('#^https?://#', '', $domainhosts), '/');


if (!defined('APP_ORIGIN') && $domainhosts !== '') {
    define('APP_ORIGIN', 'https://' . $domainhosts);
}


$GLOBALS['dbname']                     = $dbname;
$GLOBALS['usernamedb']                 = $usernamedb;
$GLOBALS['passworddb']                 = $passworddb;
$GLOBALS['dsn']                        = $dsn;
$GLOBALS['options']                    = $options;
$GLOBALS['pdo']                        = $GLOBALS['pdo'] ?? $pdo;
$GLOBALS['connect']                    = $GLOBALS['connect'] ?? $connect;
$pdo = $GLOBALS['pdo'];
$connect = $GLOBALS['connect'];
$GLOBALS['APIKEY']                     = $APIKEY;
$GLOBALS['adminnumber']                = $adminnumber;
$GLOBALS['domainhosts']                = $domainhosts;
$GLOBALS['usernamebot']                = $usernamebot;
$GLOBALS['telegramCurlTimeout']        = $telegramCurlTimeout;
$GLOBALS['telegramStrictIpValidation'] = $telegramStrictIpValidation;

require_once __DIR__ . '/proxy.php';

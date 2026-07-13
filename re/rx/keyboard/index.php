<?php
require_once dirname(__DIR__, 2) . '/_error_log.php';
require_once dirname(__DIR__) . '/load_manifest.php';
if (!defined('REFACTORED_LEGACY_ROOT')) {
    define('REFACTORED_LEGACY_ROOT', dirname(__DIR__, 3));
}
@chdir(REFACTORED_LEGACY_ROOT);

$__rx_bundle = rx_prepare_manifest_bundle(__DIR__);
try {
    require $__rx_bundle;
} catch (Throwable $__rx_throwable) {
    rx_log_event('RX_BUNDLE_THROWABLE', $__rx_throwable->getMessage(), [
        'class' => get_class($__rx_throwable),
        'file'  => $__rx_throwable->getFile(),
        'line'  => $__rx_throwable->getLine(),
    ]);
    throw $__rx_throwable;
}
unset($__rx_bundle);

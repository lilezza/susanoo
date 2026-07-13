<?php
/**
 * Build (or reuse) a single PHP bundle from a manifest so OPcache can cache it.
 * Callers MUST `require` the returned path at top-level scope so early `return`
 * statements keep the same semantics as the old eval() concat.
 */
if (!function_exists('rx_prepare_manifest_bundle')) {
    function rx_prepare_manifest_bundle(string $moduleDir): string
    {
        $moduleDir = rtrim($moduleDir, '/\\');
        $manifestPath = $moduleDir . DIRECTORY_SEPARATOR . 'manifest.php';
        if (!is_file($manifestPath)) {
            throw new RuntimeException('Missing manifest: ' . $manifestPath);
        }

        $parts = require $manifestPath;
        if (!is_array($parts) || $parts === []) {
            throw new RuntimeException('Invalid manifest: ' . $manifestPath);
        }

        $moduleName = basename($moduleDir);
        $root = defined('REFACTORED_LEGACY_ROOT')
            ? REFACTORED_LEGACY_ROOT
            : dirname($moduleDir, 3);
        $cacheDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $bundlePath = $cacheDir . DIRECTORY_SEPARATOR . 'rx_bundle_' . $moduleName . '.php';
        $needsRebuild = !is_file($bundlePath);
        if (!$needsRebuild) {
            $bundleMtime = (int) @filemtime($bundlePath);
            if ($bundleMtime <= 0 || (int) @filemtime($manifestPath) > $bundleMtime) {
                $needsRebuild = true;
            } else {
                foreach ($parts as $part) {
                    $partPath = $moduleDir . DIRECTORY_SEPARATOR . $part;
                    if (!is_file($partPath) || (int) @filemtime($partPath) > $bundleMtime) {
                        $needsRebuild = true;
                        break;
                    }
                }
            }
        }

        if ($needsRebuild) {
            $code = "<?php\n/* auto-generated rx bundle: {$moduleName} — do not edit */\n";
            foreach ($parts as $part) {
                $partPath = $moduleDir . DIRECTORY_SEPARATOR . $part;
                if (!is_file($partPath)) {
                    if (function_exists('rx_log_event')) {
                        rx_log_event('RX_MISSING_PART', $partPath, ['module' => $moduleName]);
                    }
                    throw new RuntimeException('Missing refactored part: ' . $partPath);
                }
                $raw = file_get_contents($partPath);
                if ($raw === false) {
                    throw new RuntimeException('Unable to read refactored part: ' . $partPath);
                }
                $raw = preg_replace('/^<\?php(\r\n|\r|\n)/', '', $raw, 1);
                $code .= "\n/* >>> {$part} */\n" . $raw;
            }

            $tmp = $bundlePath . '.tmp.' . getmypid();
            if (@file_put_contents($tmp, $code, LOCK_EX) === false) {
                // Cache not writable (common on locked-down hosts) — fall back to inline eval path marker.
                return rx_prepare_manifest_eval_fallback($moduleDir, $parts, $code);
            }
            if (!@rename($tmp, $bundlePath)) {
                @unlink($bundlePath);
                if (!@rename($tmp, $bundlePath)) {
                    @unlink($tmp);
                    return rx_prepare_manifest_eval_fallback($moduleDir, $parts, $code);
                }
            }
        }

        return $bundlePath;
    }
}

if (!function_exists('rx_prepare_manifest_eval_fallback')) {
    /**
     * When storage/cache is not writable, write a one-shot eval payload into a
     * request-local temp file so callers can still `require` at top level.
     * @param array<int,string> $parts
     */
    function rx_prepare_manifest_eval_fallback(string $moduleDir, array $parts, string $code): string
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rx_bundle_' . basename($moduleDir) . '_' . md5($moduleDir) . '.php';
        $mtimeOk = is_file($tmp);
        if ($mtimeOk) {
            $tmpMtime = (int) @filemtime($tmp);
            foreach ($parts as $part) {
                $partPath = $moduleDir . DIRECTORY_SEPARATOR . $part;
                if (!is_file($partPath) || (int) @filemtime($partPath) > $tmpMtime) {
                    $mtimeOk = false;
                    break;
                }
            }
        }
        if (!$mtimeOk) {
            @file_put_contents($tmp, $code, LOCK_EX);
        }
        return $tmp;
    }
}

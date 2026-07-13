<?php
/**
 * Session debug logger for Susanoo cPanel perf investigation.
 * Writes NDJSON to the Cursor debug log path (and optionally mirrors to logs/).
 */
if (!function_exists('rx_debug_perf_log')) {
    function rx_debug_perf_log(string $hypothesisId, string $location, string $message, array $data = [], string $runId = 'pre'): void
    {
        $payload = [
            'sessionId' => '428f28',
            'runId' => $runId,
            'hypothesisId' => $hypothesisId,
            'location' => $location,
            'message' => $message,
            'data' => $data,
            'timestamp' => (int) round(microtime(true) * 1000),
        ];
        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }
        $line .= "\n";

        $logDir = (defined('REFACTORED_LEGACY_ROOT') ? REFACTORED_LEGACY_ROOT : dirname(__DIR__))
            . DIRECTORY_SEPARATOR . 'logs';
        if (is_dir($logDir) || @mkdir($logDir, 0755, true)) {
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'debug-perf.ndjson', $line, FILE_APPEND | LOCK_EX);
        }
    }
}

if (!function_exists('rx_debug_perf_time')) {
    function rx_debug_perf_time(): float
    {
        return microtime(true);
    }
}

if (!function_exists('rx_debug_perf_ms')) {
    function rx_debug_perf_ms(float $startedAt): float
    {
        return round((microtime(true) - $startedAt) * 1000, 3);
    }
}

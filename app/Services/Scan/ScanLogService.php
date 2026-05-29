<?php

namespace App\Services\Scan;

use App\Models\ScanHistory;
use App\Models\ScanLog;

class ScanLogService
{
    /**
     * @param array<int, string> $secrets
     */
    public function write(ScanHistory $scanHistory, string $level, string $message, array $context = [], array $secrets = []): void
    {
        $sanitizedMessage = $this->maskSecrets($message, $secrets);
        $sanitizedContext = $this->sanitizeContext($context, $secrets);

        $scanHistory->logs()->create([
            'log_level' => $level,
            'message' => $sanitizedMessage,
            'context' => $sanitizedContext ?: null,
        ]);
    }

    /**
     * @param array<int, string> $secrets
     */
    public function writeOutput(ScanHistory $scanHistory, string $stdout, string $stderr, array $secrets = []): void
    {
        foreach ($this->splitOutput($stdout) as $line) {
            $this->write($scanHistory, 'info', $line, [], $secrets);
        }

        foreach ($this->splitOutput($stderr) as $line) {
            $this->write($scanHistory, 'error', $line, [], $secrets);
        }
    }

    /**
     * @param array<int, string> $secrets
     */
    public function maskSecrets(string $value, array $secrets = []): string
    {
        $masked = $value;

        foreach (array_filter($secrets) as $secret) {
            $masked = str_replace($secret, '***', $masked);
        }

        return $masked;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<int, string> $secrets
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context, array $secrets): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $sanitized[$key] = is_string($value)
                ? $this->maskSecrets($value, $secrets)
                : $value;
        }

        return $sanitized;
    }

    /**
     * @return array<int, string>
     */
    private function splitOutput(string $output): array
    {
        return collect(preg_split("/\r\n|\n|\r/", trim($output)))
            ->filter(fn (?string $line) => filled($line))
            ->values()
            ->all();
    }
}

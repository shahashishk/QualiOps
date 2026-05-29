<?php

namespace Tests\Unit;

use App\Services\Audit\AuditLogService;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    public function test_it_masks_sensitive_values(): void
    {
        $service = app(AuditLogService::class);

        $sanitized = $service->sanitizeValues([
            'password' => 'secret-pass',
            'token' => 'abc123',
            'nested' => [
                'ssh_private_key' => '-----BEGIN OPENSSH PRIVATE KEY-----',
                'safe' => 'visible',
            ],
        ]);

        $this->assertSame('***', $sanitized['password']);
        $this->assertSame('***', $sanitized['token']);
        $this->assertSame('***', $sanitized['nested']['ssh_private_key']);
        $this->assertSame('visible', $sanitized['nested']['safe']);
    }
}

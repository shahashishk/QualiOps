<?php

namespace Tests\Unit;

use App\Services\Scan\WorkspaceService;
use RuntimeException;
use Tests\TestCase;

class WorkspaceServiceTest extends TestCase
{
    public function test_assert_within_workspace_rejects_path_traversal(): void
    {
        config()->set('scan.workspace_path', 'D:\\scan-workspaces');

        $service = app(WorkspaceService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Workspace path validation failed.');

        $service->assertWithinWorkspace('D:\\scan-workspaces\\..\\outside');
    }
}

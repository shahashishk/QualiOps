<?php

namespace App\Services\Scan;

use Illuminate\Support\Facades\File;

class CleanupService
{
    public function __construct(
        private readonly WorkspaceService $workspaceService,
    ) {
    }

    public function deleteWorkspace(string $workspacePath): void
    {
        if (blank($workspacePath) || ! File::exists($workspacePath)) {
            return;
        }

        $this->workspaceService->assertWithinWorkspace($workspacePath);

        File::deleteDirectory($workspacePath);
    }
}

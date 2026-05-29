<?php

namespace App\Services\Scan;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class WorkspaceService
{
    public function createWorkspace(int $queueId): array
    {
        $basePath = $this->basePath();
        File::ensureDirectoryExists($basePath);

        $workspacePath = $basePath.DIRECTORY_SEPARATOR.'scan-'.$queueId.'-'.Str::uuid()->toString();
        $sourcePath = $workspacePath.DIRECTORY_SEPARATOR.'source';

        File::ensureDirectoryExists($workspacePath);
        File::ensureDirectoryExists($sourcePath);

        return [
            'workspace_path' => $workspacePath,
            'source_path' => $sourcePath,
        ];
    }

    public function writeSshKey(string $workspacePath, string $privateKey): string
    {
        $sshDirectory = $workspacePath.DIRECTORY_SEPARATOR.'.ssh';
        $keyPath = $sshDirectory.DIRECTORY_SEPARATOR.'id_scan';

        File::ensureDirectoryExists($sshDirectory);
        File::put($keyPath, $privateKey);

        return $keyPath;
    }

    public function assertWithinWorkspace(string $path): void
    {
        $basePath = $this->normalizePath($this->basePath());
        $targetPath = $this->normalizePath($path);

        if (! str_starts_with($targetPath, $basePath)) {
            throw new RuntimeException('Workspace path validation failed.');
        }
    }

    private function basePath(): string
    {
        return (string) config('scan.workspace_path');
    }

    private function normalizePath(string $path): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $prefix = '';

        if (preg_match('/^[A-Za-z]:\\\\/', $normalized) === 1) {
            $prefix = substr($normalized, 0, 3);
            $normalized = substr($normalized, 3);
        } elseif (str_starts_with($normalized, DIRECTORY_SEPARATOR)) {
            $prefix = DIRECTORY_SEPARATOR;
            $normalized = ltrim($normalized, DIRECTORY_SEPARATOR);
        }

        $segments = preg_split('#[\\\\/]+#', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $resolved = [];

        foreach ($segments as $segment) {
            if ($segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($resolved);
                continue;
            }

            $resolved[] = $segment;
        }

        return rtrim($prefix.implode(DIRECTORY_SEPARATOR, $resolved), DIRECTORY_SEPARATOR);
    }
}

<?php

namespace App\Services\Git;

class GitRepositoryResolver
{
    public function resolve(string $providerType, string $gitUrl): ?array
    {
        return match ($providerType) {
            'gitlab' => $this->resolveGitLab($gitUrl),
            'github' => $this->resolveGitHub($gitUrl),
            'bitbucket' => $this->resolveBitbucket($gitUrl),
            default => null,
        };
    }

    private function resolveGitLab(string $gitUrl): ?array
    {
        $path = $this->extractPath($gitUrl);

        if (blank($path)) {
            return null;
        }

        return [
            'project_path' => rawurlencode($path),
        ];
    }

    private function resolveGitHub(string $gitUrl): ?array
    {
        $parts = $this->extractSegments($gitUrl);

        if (count($parts) < 2) {
            return null;
        }

        return [
            'owner' => $parts[0],
            'repo' => $parts[1],
        ];
    }

    private function resolveBitbucket(string $gitUrl): ?array
    {
        $parts = $this->extractSegments($gitUrl);

        if (count($parts) < 2) {
            return null;
        }

        return [
            'workspace' => $parts[0],
            'repo_slug' => $parts[1],
        ];
    }

    private function extractPath(string $gitUrl): ?string
    {
        if (preg_match('/^[^@]+@[^:]+:(.+?)(?:\.git)?$/', $gitUrl, $matches)) {
            return trim($matches[1], '/');
        }

        $path = parse_url($gitUrl, PHP_URL_PATH);

        if (! is_string($path) || blank($path)) {
            return null;
        }

        return preg_replace('/\.git$/', '', trim($path, '/'));
    }

    private function extractSegments(string $gitUrl): array
    {
        $path = $this->extractPath($gitUrl);

        if (blank($path)) {
            return [];
        }

        return array_values(array_filter(explode('/', $path)));
    }
}

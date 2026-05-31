<?php

namespace App\Services\Git;

class GitRepositoryResolver
{
    public function resolve(string $providerType, string $gitUrl, ?string $baseUrl = null): ?array
    {
        return match ($providerType) {
            'gitlab' => $this->resolveGitLab($gitUrl, $baseUrl),
            'github' => $this->resolveGitHub($gitUrl),
            'bitbucket' => $this->resolveBitbucket($gitUrl),
            default => null,
        };
    }

    public function matchesConnectionHost(string $gitUrl, ?string $baseUrl): bool
    {
        $gitHost = $this->extractHost($gitUrl);
        $connectionHost = $this->extractHost((string) $baseUrl);

        if (blank($gitHost) || blank($connectionHost)) {
            return false;
        }

        return strtolower((string) $gitHost) === strtolower((string) $connectionHost);
    }

    private function resolveGitLab(string $gitUrl, ?string $baseUrl = null): ?array
    {
        $path = $this->extractPath($gitUrl);

        if (blank($path)) {
            return null;
        }

        $path = $this->stripBasePathPrefix($path, $baseUrl);

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

    private function stripBasePathPrefix(string $path, ?string $baseUrl): string
    {
        if (blank($baseUrl)) {
            return $path;
        }

        $basePath = parse_url($baseUrl, PHP_URL_PATH);

        if (! is_string($basePath) || blank($basePath) || $basePath === '/') {
            return $path;
        }

        $normalizedBasePath = trim($basePath, '/');

        if (str_starts_with($path, $normalizedBasePath.'/')) {
            return substr($path, strlen($normalizedBasePath) + 1);
        }

        return $path;
    }

    private function extractHost(string $url): ?string
    {
        if (preg_match('/^[^@]+@([^:]+):(.+?)(?:\.git)?$/', $url, $matches)) {
            return $matches[1];
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && filled($host) ? $host : null;
    }
}

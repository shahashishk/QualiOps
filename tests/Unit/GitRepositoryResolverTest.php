<?php

namespace Tests\Unit;

use App\Services\Git\GitRepositoryResolver;
use PHPUnit\Framework\TestCase;

class GitRepositoryResolverTest extends TestCase
{
    public function test_it_resolves_github_repository_from_https_url(): void
    {
        $resolver = new GitRepositoryResolver();

        $resolved = $resolver->resolve('github', 'https://github.com/acme/platform-api.git');

        $this->assertSame([
            'owner' => 'acme',
            'repo' => 'platform-api',
        ], $resolved);
    }

    public function test_it_resolves_gitlab_repository_from_ssh_url(): void
    {
        $resolver = new GitRepositoryResolver();

        $resolved = $resolver->resolve('gitlab', 'git@gitlab.example.com:team/core/quality-portal.git');

        $this->assertSame([
            'project_path' => 'team%2Fcore%2Fquality-portal',
        ], $resolved);
    }
}

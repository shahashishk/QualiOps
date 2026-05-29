<?php

namespace Tests\Unit;

use App\Services\Scan\StackDetectionService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StackDetectionServiceTest extends TestCase
{
    public function test_it_detects_multiple_stacks_for_auto_mode(): void
    {
        $path = storage_path('framework/testing/stack-detection-auto');

        File::deleteDirectory($path);
        File::ensureDirectoryExists($path);
        File::put($path.DIRECTORY_SEPARATOR.'angular.json', '{}');
        File::put($path.DIRECTORY_SEPARATOR.'package.json', '{}');
        File::put($path.DIRECTORY_SEPARATOR.'pubspec.yaml', 'name: test');

        $service = new StackDetectionService();

        $detected = $service->detect($path, 'auto');

        $this->assertSame(['angular', 'flutter', 'node'], $detected);

        File::deleteDirectory($path);
    }

    public function test_it_defaults_to_generic_when_no_stack_files_exist(): void
    {
        $path = storage_path('framework/testing/stack-detection-generic');

        File::deleteDirectory($path);
        File::ensureDirectoryExists($path);

        $service = new StackDetectionService();

        $detected = $service->detect($path, 'auto');

        $this->assertSame(['generic'], $detected);

        File::deleteDirectory($path);
    }
}

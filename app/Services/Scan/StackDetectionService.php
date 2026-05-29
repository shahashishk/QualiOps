<?php

namespace App\Services\Scan;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class StackDetectionService
{
    /**
     * @return array<int, string>
     */
    public function detect(string $sourcePath, string $requestedScanType): array
    {
        if ($requestedScanType !== 'auto' && $requestedScanType !== 'mixed') {
            return [$requestedScanType];
        }

        $detected = [];

        if ($this->containsFileEnding($sourcePath, ['.csproj', '.sln'])) {
            $detected[] = 'dotnet';
        }

        if ($this->containsFileNamed($sourcePath, 'angular.json')) {
            $detected[] = 'angular';
        }

        if ($this->containsFileNamed($sourcePath, 'pubspec.yaml')) {
            $detected[] = 'flutter';
        }

        if ($this->containsFileNamed($sourcePath, 'package.json')) {
            $detected[] = 'node';
        }

        if ($this->containsFileNamed($sourcePath, 'sonar-project.properties')) {
            $detected[] = 'generic';
        }

        $detected = array_values(array_unique($detected));

        if ($detected === []) {
            return ['generic'];
        }

        if ($requestedScanType === 'mixed') {
            return $detected;
        }

        return count($detected) > 1 ? $detected : [$detected[0]];
    }

    /**
     * @param array<int, string> $endings
     */
    private function containsFileEnding(string $sourcePath, array $endings): bool
    {
        foreach ($this->files($sourcePath) as $file) {
            foreach ($endings as $ending) {
                if (str_ends_with(strtolower($file->getFilename()), strtolower($ending))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function containsFileNamed(string $sourcePath, string $expectedName): bool
    {
        foreach ($this->files($sourcePath) as $file) {
            if (strcasecmp($file->getFilename(), $expectedName) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Generator<int, SplFileInfo>
     */
    private function files(string $sourcePath): \Generator
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                yield $file;
            }
        }
    }
}

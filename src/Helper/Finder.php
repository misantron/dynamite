<?php

declare(strict_types=1);

namespace Dynamite\Helper;

class Finder
{
    public function __construct(private readonly string $path)
    {
    }

    public function getClasses(string $interface): array
    {
        $files = [];
        /** @var \SplFileInfo $fileInfo */
        foreach ($this->createIterator($this->path) as $fileInfo) {
            if (!str_ends_with($fileInfo->getFilename(), '.php')) {
                continue;
            }

            $filepath = $fileInfo->getRealPath();
            // @codeCoverageIgnoreStart
            if ($filepath === false) {
                continue;
            }
            // @codeCoverageIgnoreEnd

            self::requireOnce($filepath);

            $files[] = $filepath;
        }

        return $this->loadClasses($files, $interface);
    }

    private function loadClasses(array $files, string $interface): array
    {
        return array_filter(
            get_declared_classes(),
            static function (string $class) use ($files, $interface): bool {
                $reflectionClass = new \ReflectionClass($class);
                $implementedInterfaces = $reflectionClass->getInterfaces();

                if (!isset($implementedInterfaces[$interface])) {
                    return false;
                }

                return \in_array($reflectionClass->getFileName(), $files, true);
            }
        );
    }

    private function createIterator(string $path): \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    private static function requireOnce(string $path): void
    {
        require_once $path;
    }
}

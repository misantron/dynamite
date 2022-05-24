<?php

declare(strict_types=1);

namespace Dynamite\Helper;

class DirectoryResolver
{
    public function __construct(private readonly string $path)
    {
    }

    public function getClasses(string $interface): iterable
    {
        $files = [];
        foreach ($this->createIterator($this->path) as $fileInfo) {
            $filepath = $fileInfo->getRealPath();
            if ($filepath === false) {
                continue;
            }

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

    /**
     * @param string $path
     * @return \SplFileInfo[]
     */
    private function createIterator(string $path): \OuterIterator
    {
        return new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            ),
            '/^.+\.php$/i',
            \RegexIterator::GET_MATCH
        );
    }

    private static function requireOnce(string $path): void
    {
        require_once $path;
    }
}

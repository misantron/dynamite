<?php

declare(strict_types=1);

namespace Dynamite;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Loader
{
    private array $tables = [];

    private array $fixtures = [];

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly NormalizerInterface $serializer
    ) {
    }

    public function loadFromDirectory(string $path): void
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException('Invalid directory path: ' . $path);
        }

        $iterator = $this->createDirectoryIterator($path);

        $files = [];
        /** @var \SplFileInfo $info */
        foreach ($iterator as $info) {
            if ($info->getExtension() !== 'php') {
                continue;
            }

            $filepath = $info->getRealPath();
            // @codeCoverageIgnoreStart
            if ($filepath === false) {
                continue;
            }
            // @codeCoverageIgnoreEnd

            self::requireOnce($filepath);

            $files[] = $filepath;
        }

        $this->loadClasses($files);
    }

    public function addTable(TableInterface $table): void
    {
        if (isset($this->tables[$table::class])) {
            return;
        }

        $table->setValidator($this->validator);
        $table->setNormalizer($this->serializer);

        $this->tables[$table::class] = $table;
    }

    public function addFixture(FixtureInterface $fixture): void
    {
        if (isset($this->fixtures[$fixture::class])) {
            return;
        }

        $fixture->setValidator($this->validator);

        $this->fixtures[$fixture::class] = $fixture;
    }

    /**
     * @return array<string, TableInterface>
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * @return array<string, FixtureInterface>
     */
    public function getFixtures(): array
    {
        return $this->fixtures;
    }

    private function loadClasses(array $files): void
    {
        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $reflectionClass = new \ReflectionClass($className);
            $interfaces = $reflectionClass->getInterfaces();

            if (!\in_array($reflectionClass->getFileName(), $files, true)) {
                continue;
            }

            if (isset($interfaces[TableInterface::class])) {
                $this->tables[$className] = $this->createTable($className);
            }
            if (isset($interfaces[FixtureInterface::class])) {
                $this->fixtures[$className] = $this->createFixture($className);
            }
        }
    }

    private function createDirectoryIterator(string $path): \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    private function createTable(string $className): TableInterface
    {
        $table = new $className();
        $table->setValidator($this->validator);
        $table->setNormalizer($this->serializer);

        return $table;
    }

    private function createFixture(string $className): FixtureInterface
    {
        $fixture = new $className();
        $fixture->setValidator($this->validator);

        return $fixture;
    }

    private static function requireOnce(string $path): void
    {
        require_once $path;
    }
}

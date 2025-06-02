<?php

declare(strict_types=1);

namespace Dynamite;

use Dynamite\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

class Loader
{
    /**
     * @var array<string, TableInterface>
     */
    private array $tables = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private array $groupedTablesMapping = [];

    /**
     * @var array<string, FixtureInterface>
     */
    private array $fixtures = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private array $groupedFixturesMapping = [];

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

            Assert::notFalse($filepath, 'Filepath is not valid');

            $this->requireOnce($filepath);

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

        $reflectionClass = new \ReflectionClass($table);
        $groups = $this->getGroups($reflectionClass);

        $this->tables[$table::class] = $table;

        if ($groups !== null) {
            foreach ($groups as $group) {
                $this->groupedTablesMapping[$group][$table::class] = true;
            }
        }
    }

    public function addFixture(FixtureInterface $fixture): void
    {
        if (isset($this->fixtures[$fixture::class])) {
            return;
        }

        $fixture->setValidator($this->validator);

        $reflectionClass = new \ReflectionClass($fixture);
        $groups = $this->getGroups($reflectionClass);

        $this->fixtures[$fixture::class] = $fixture;

        if ($groups !== null) {
            foreach ($groups as $group) {
                $this->groupedFixturesMapping[$group][$fixture::class] = true;
            }
        }
    }

    /**
     * @param list<string> $groups
     *
     * @return list<TableInterface>
     */
    public function getTables(array $groups = []): array
    {
        if (\count($groups) < 1) {
            return array_values($this->tables);
        }

        $filteredTables = [];
        foreach ($this->tables as $table) {
            foreach ($groups as $group) {
                if (isset($this->groupedTablesMapping[$group][$table::class])) {
                    $filteredTables[$table::class] = $table;
                    continue 2;
                }
            }
        }

        return array_values($filteredTables);
    }

    /**
     * @param list<string> $groups
     *
     * @return list<FixtureInterface>
     */
    public function getFixtures(array $groups = []): array
    {
        if (\count($groups) < 1) {
            return array_values($this->fixtures);
        }

        $filteredFixtures = [];
        foreach ($this->fixtures as $fixture) {
            foreach ($groups as $group) {
                if (isset($this->groupedFixturesMapping[$group][$fixture::class])) {
                    $filteredFixtures[$fixture::class] = $fixture;
                    continue 2;
                }
            }
        }

        return array_values($filteredFixtures);
    }

    /**
     * @param array<int, string> $files
     */
    private function loadClasses(array $files): void
    {
        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $reflectionClass = new \ReflectionClass($className);
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            if (!\in_array($reflectionClass->getFileName(), $files, true)) {
                continue;
            }

            $interfaces = $reflectionClass->getInterfaces();
            $groups = $this->getGroups($reflectionClass);

            if (isset($interfaces[TableInterface::class])) {
                $this->tables[$className] = $this->createTable($className);
            }

            if (isset($interfaces[FixtureInterface::class])) {
                $this->fixtures[$className] = $this->createFixture($className);
            }

            if ($groups !== null) {
                foreach ($groups as $group) {
                    if (isset($interfaces[TableInterface::class])) {
                        $this->groupedTablesMapping[$group][$className] = true;
                    }

                    if (isset($interfaces[FixtureInterface::class])) {
                        $this->groupedFixturesMapping[$group][$className] = true;
                    }
                }
            }
        }
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     * @return list<string>|null
     */
    private function getGroups(\ReflectionClass $reflectionClass): ?array
    {
        $attributes = $reflectionClass->getAttributes(Groups::class);
        if (\count($attributes) < 1) {
            return null;
        }

        /** @var Groups $groupsAttribute */
        $groupsAttribute = $attributes[0]->newInstance();

        return $groupsAttribute->getNames();
    }

    /**
     * @return \RecursiveIteratorIterator<\RecursiveDirectoryIterator>
     */
    private function createDirectoryIterator(string $path): \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    private function createTable(string $className): TableInterface
    {
        /** @var TableInterface $table */
        $table = new $className();
        $table->setValidator($this->validator);
        $table->setNormalizer($this->serializer);

        return $table;
    }

    private function createFixture(string $className): FixtureInterface
    {
        /** @var FixtureInterface $fixture */
        $fixture = new $className();
        $fixture->setValidator($this->validator);

        return $fixture;
    }

    private function requireOnce(string $path): void
    {
        require_once $path;
    }
}

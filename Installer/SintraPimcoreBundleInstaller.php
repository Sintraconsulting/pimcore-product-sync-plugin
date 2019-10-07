<?php

namespace SintraPimcoreBundle\Installer;

use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;
use Pimcore\Migrations\MigrationManager;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class SintraPimcoreBundleInstaller extends MigrationInstaller{
    
    /**
     * @var string
     */
    private $installSourcesPath;

    /**
     * @var array
     */
    private $tablesToInstall = array(
        "custom_log" => 
            "CREATE TABLE IF NOT EXISTS `custom_log` (
                `gravity` VARCHAR(45) NOT NULL,
                `class` VARCHAR(255) NOT NULL,
                `action` VARCHAR(255) NOT NULL,
                `flow` VARCHAR(255) NOT NULL,
                `description` TEXT NOT NULL,
                `timestamp` INT(11) UNSIGNED NOT NULL);"
    );
    
    /**
     * @var array
     */
    private $classesToInstall = [
            'Product' => 'SC_PROD',
            'TargetServer' => 'SC_TSRV',
    ];
    
    public function __construct(
        BundleInterface $bundle,
        ConnectionInterface $connection,
        MigrationManager $migrationManager
    ) {
        $this->installSourcesPath = __DIR__ . '/../Resources/install';

        parent::__construct($bundle, $connection, $migrationManager);
    }
    
    public function migrateInstall(Schema $schema, Version $version) {
        $this->installFieldCollections();
        $this->installClasses();
        $this->installObjectBricks();
        $this->installTables($schema, $version);
    }

    public function migrateUninstall(Schema $schema, Version $version) {
        $this->uninstallTables($schema);
    }
    
    private function installTables(Schema $schema, Version $version)
    {
        foreach ($this->tablesToInstall as $name => $statement) {
            if ($schema->hasTable($name)) {
                $this->outputWriter->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping table "%s" as it already exists',
                    $name
                ));

                continue;
            }

            $version->addSql($statement);
        }
    }
    
    private function installFieldCollections()
    {
        $fieldCollections = $this->findInstallFiles(
            $this->installSourcesPath . '/fieldcollection_sources',
            '/^fieldcollection_(.*)_export\.json$/'
        );

        foreach ($fieldCollections as $key => $path) {
            if ($fieldCollection = Fieldcollection\Definition::getByKey($key)) {
                if ($fieldCollection) {
                    $this->outputWriter->write(sprintf(
                        '     <comment>WARNING:</comment> Skipping field collection "%s" as it already exists',
                        $key
                    ));

                    continue;
                }
            } else {
                $fieldCollection = new Fieldcollection\Definition();
                $fieldCollection->setKey($key);
            }

            $data = file_get_contents($path);
            $success = Service::importFieldCollectionFromJson($fieldCollection, $data);

            if (!$success) {
                throw new AbortMigrationException(sprintf(
                    'Failed to create field collection "%s"',
                    $key
                ));
            }
        }
    }
    
    /**
     * Finds objectbrick/fieldcollection sources by path returns a result list
     * indexed by element name.
     *
     * @param string $directory
     * @param string $pattern
     *
     * @return array
     */
    private function findInstallFiles(string $directory, string $pattern): array
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($directory)
            ->name($pattern);

        $results = [];
        foreach ($finder as $file) {
            if (preg_match($pattern, $file->getFilename(), $matches)) {
                $key = $matches[1];
                $results[$key] = $file->getRealPath();
            }
        }

        return $results;
    }
    
    private function installClasses()
    {
        $classes = $this->getClassesToInstall();

        $mapping = $this->classesToInstall;

        foreach ($classes as $key => $path) {
            $class = ClassDefinition::getByName($key);

            if ($class) {
                $this->outputWriter->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping class "%s" as it already exists',
                    $key
                ));

                continue;
            }

            $class = new ClassDefinition();

            $classId = $mapping[$key];

            $class->setName($key);
            $class->setId($classId);

            $data = file_get_contents($path);
            $success = Service::importClassDefinitionFromJson($class, $data, false, true);

            if (!$success) {
                throw new AbortMigrationException(sprintf(
                    'Failed to create class "%s"',
                    $key
                ));
            }
        }
    }
    
    private function getClassesToInstall(): array
    {
        $result = [];
        foreach (array_keys($this->classesToInstall) as $className) {
            $filename = sprintf('class_%s_export.json', $className);
            $path = $this->installSourcesPath . '/class_sources/' . $filename;
            $path = realpath($path);

            if (false === $path || !is_file($path)) {
                throw new AbortMigrationException(sprintf(
                    'Class export for class "%s" was expected in "%s" but file does not exist',
                    $className,
                    $path
                ));
            }

            $result[$className] = $path;
        }

        return $result;
    }

    private function installObjectBricks()
    {
        $bricks = $this->findInstallFiles(
            $this->installSourcesPath . '/objectbrick_sources',
            '/^objectbrick_(.*)_export\.json$/'
        );

        foreach ($bricks as $key => $path) {
            if ($brick = Objectbrick\Definition::getByKey($key)) {
                $this->outputWriter->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping object brick "%s" as it already exists',
                    $key
                ));

                continue;
            } else {
                $brick = new Objectbrick\Definition();
                $brick->setKey($key);
            }

            $data = file_get_contents($path);
            $success = Service::importObjectBrickFromJson($brick, $data);

            if (!$success) {
                throw new AbortMigrationException(sprintf(
                    'Failed to create object brick "%s"',
                    $key
                ));
            }
        }
    }
    
    private function uninstallTables(Schema $schema)
    {
        foreach (array_keys($this->tablesToInstall) as $table) {
            if (!$schema->hasTable($table)) {
                $this->outputWriter->write(sprintf(
                    '     <comment>WARNING:</comment> Not dropping table "%s" as it doesn\'t exist',
                    $table
                ));

                continue;
            }

            $schema->dropTable($table);
        }
    }

}

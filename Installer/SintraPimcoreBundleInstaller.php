<?php

namespace SintraPimcoreBundle\Installer;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;

class SintraPimcoreBundleInstaller extends MigrationInstaller{
    
    public function migrateInstall(Schema $schema, Version $version) {
        $version->addSql("CREATE TABLE IF NOT EXISTS `custom_log` (
            `gravity` VARCHAR(45) NOT NULL,
            `class` VARCHAR(255) NOT NULL,
            `action` VARCHAR(255) NOT NULL,
            `flow` VARCHAR(255) NOT NULL,
            `description` TEXT NOT NULL,
            `timestamp` INT(11) UNSIGNED NOT NULL);"
        );
    }

    public function migrateUninstall(Schema $schema, Version $version) {
        
    }

}
<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20200910184756 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
ALTER TABLE `PresentationMaterial` CHANGE `ClassName` `ClassName` ENUM('PresentationMaterial','PresentationLink','PresentationSlide','PresentationVideo', 'PresentationMediaUpload') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'PresentationMaterial';
SQL;
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sql = <<<SQL
ALTER TABLE `PresentationMaterial` CHANGE `ClassName` `ClassName` ENUM('PresentationMaterial','PresentationLink','PresentationSlide','PresentationVideo') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'PresentationMaterial';
SQL;
        $this->addSql($sql);

    }
}

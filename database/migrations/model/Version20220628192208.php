<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20220628192208 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
ALTER TABLE PresentationActionType MODIFY ClassName enum('PresentationActionType', 'SelectionPlanActionType') DEFAULT 'PresentationActionType';
SQL;
        $builder = new Builder($schema);

        if($schema->hasTable("PresentationActionType") && !$builder->hasColumn("Sponsor", "SelectionPlanID")) {
            $builder->table('PresentationActionType', function (Table $table) {
                $table->integer('SelectionPlanID')->setNotnull(false)->setDefault('NULL');
                $table->foreign("SelectionPlan", "SelectionPlanID", "ID");
            });
            $this->addSql($sql);
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema): void
    {
        $sql = <<<SQL
ALTER TABLE SummitAbstractLocation MODIFY ClassName enum('PresentationActionType') DEFAULT 'PresentationActionType';
SQL;
        $builder = new Builder($schema);
        if($schema->hasTable("PresentationActionType") && $builder->hasColumn("PresentationActionType", "SelectionPlanID")) {
            $builder->table('PresentationActionType', function (Table $table) {
                $table->dropColumn('SelectionPlanID');
            });
            $this->addSql($sql);
        }
    }
}

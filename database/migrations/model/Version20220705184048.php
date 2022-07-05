<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20220705184048 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if($schema->hasTable("SponsoredProject")) {
            $builder->table('SponsoredProject', function (Table $table) {
                $table->string('NavBarTitle')->setNotnull(false)->setLength(255);
                $table->boolean('ShouldShowOnNavBar')->setNotnull(true)->setDefault(true);
                $table->string('LearnMoreLink')->setNotnull(false)->setLength(255);
                $table->text('LearnMoreText')->setNotnull(false);
                $table->string('SiteURL')->setNotnull(false)->setLength(255);
                $table->integer("LogoID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("LogoID", "LogoID");
                $table->foreign("File", "LogoID", "ID", ["onDelete" => "CASCADE"]);
            });
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SponsoredProject")) {
            $builder->table('SponsoredProject', function (Table $table) {
                $table->dropColumn('NavBarTitle');
                $table->dropColumn('ShouldShowOnNavBar');
                $table->dropColumn('LearnMoreLink');
                $table->dropColumn('LearnMoreText');
                $table->dropColumn('SiteURL');
                $table->dropColumn('LogoID');
            });
        }
    }
}

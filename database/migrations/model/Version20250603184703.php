<?php namespace Database\Migrations\Model;
/**
 * Copyright 2025 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use Database\Utils\DBHelpers;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
use models\summit\SummitSponsorshipType;

/**
 * Class Version20250603184703
 * @package Database\Migrations\Model
 */
class Version20250603184703 extends AbstractMigration
{
    const TableName = "SummitSponsorship";

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable(self::TableName)) {
            $builder->create(self::TableName, function (Table $table) {

                $table->integer('ID', true, false);
                $table->primary('ID');
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName')->setDefault('SummitSponsorship');

                // FK
                $table->integer("SponsorID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SponsorID", "SponsorID");
                $table->foreign("Sponsor", "SponsorID", "ID", ["onDelete" => "CASCADE"], 'FK_Sponsor_SummitSponsorship');

                // FK
                $table->integer("TypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("TypeID", "TypeID");
                $table->foreign("Summit_SponsorshipType", "TypeID", "ID", ["onDelete" => "SET NULL"], 'FK_Summit_SponsorshipType_SummitSponsorship');

                $table->unique(['SponsorID', 'TypeID'], "SponsorID_TypeID");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);

        $builder->dropIfExists(self::TableName);
    }
}

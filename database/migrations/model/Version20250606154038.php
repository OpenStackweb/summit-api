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

final class Version20250606154038 extends AbstractMigration
{
    const TableName = "Sponsor";

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $db_name = DB::connection("model")->getDatabaseName();

        $sql = <<<SQL
INSERT INTO SummitSponsorship(Created, LastEdited, ClassName, SponsorID, TypeID)
SELECT NOW(), NOW(),'SummitSponsorship', s.ID, st.ID
FROM Sponsor s INNER JOIN Summit_SponsorshipType st ON s.SummitSponsorshipTypeID = st.ID;
SQL;

        $this->addSql($sql);

        if(DBHelpers::existsFK($db_name, self::TableName, 'FK_Sponsor_SummitSponsorshipType')) {
            DBHelpers::dropFK($db_name, self::TableName, 'FK_Sponsor_SummitSponsorshipType');
        }

        $builder = new Builder($schema);
        if ($builder->hasTable(self::TableName) && $builder->hasColumn(self::TableName, "SummitSponsorshipTypeID")) {
           $builder->table(self::TableName, function (Table $table) {
                $table->dropColumn("SummitSponsorshipTypeID");
           });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}

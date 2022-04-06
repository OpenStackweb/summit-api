<?php namespace Database\Migrations\Model;
/**
 * Copyright 2019 OpenStack Foundation
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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
use models\summit\SummitSelectedPresentationList;

/**
 * Class Version20220405205925
 * @package Database\Migrations\Model
 */
final class Version20220405205925 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
DELETE FROM SummitSelectedPresentation 
WHERE NOT EXISTS (SELECT 1 FROM Member WHERE Member.ID = SummitSelectedPresentation.MemberID) AND SummitSelectedPresentation.MemberID IS NOT NULL;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
DELETE FROM SummitSelectedPresentation 
WHERE NOT EXISTS (SELECT 1 FROM Presentation WHERE Presentation.ID = SummitSelectedPresentation.PresentationID) AND SummitSelectedPresentation.PresentationID IS NOT NULL;
SQL;
        $this->addSql($sql);

        $builder = new Builder($schema);
        if($schema->hasTable("SummitSelectedPresentation")) {
            $builder->table('SummitSelectedPresentation', function (Table $table) {
                // FK
                $table->foreign("SummitSelectedPresentationList", "SummitSelectedPresentationListID", "ID", ["onDelete" => "CASCADE"]);
                $table->foreign('Member', 'MemberID', 'ID',  ["onDelete" => "CASCADE"]);
                $table->foreign('Presentation', 'PresentationID', 'ID',  ["onDelete" => "CASCADE"]);
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

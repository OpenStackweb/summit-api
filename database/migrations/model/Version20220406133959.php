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
use Database\Utils\DBHelpers;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Class Version20220406133959
 * @package Database\Migrations\Model
 */
final class Version20220406133959 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
       if(!DBHelpers::existsFK(env('SS_DATABASE'), "SummitSelectedPresentation", "FK_SummitSelectedPresentation_Member_SummitSelectedPresentationList") &&
           $schema->hasTable("SummitSelectedPresentation")) {
            $builder->table('SummitSelectedPresentation', function (Table $table) {
                // FK
                $table->foreign("SummitSelectedPresentationList", "SummitSelectedPresentationListID", "ID", ["onDelete" => "CASCADE"], 'FK_SummitSelectedPresentation_Member_SummitSelectedPresentationList');
                $table->foreign('Member', 'MemberID', 'ID',  ["onDelete" => "CASCADE"], 'FK_SummitSelectedPresentation_Member');
                $table->foreign('Presentation', 'PresentationID', 'ID',  ["onDelete" => "CASCADE"],'FK_SummitSelectedPresentation_Presentation');
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

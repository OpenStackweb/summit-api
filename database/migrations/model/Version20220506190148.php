<?php namespace Database\Migrations\Model;
/**
 * Copyright 2022 OpenStack Foundation
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
/**
 * Class Version20220506190148
 * @package Database\Migrations\Model
 */
final class Version20220506190148 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if(!$schema->hasTable("PresentationTrackChairScore")) {
            $builder->create('PresentationTrackChairScore', function (Table $table) {
                $table->increments('ID');
                $table->dateTime("Created")->setNotnull(true);
                $table->dateTime("LastEdited")->setNotnull(true);
                $table->string("ClassName", 50)->setDefault("PresentationTrackChairScore");

                $table->integer("TypeID" );
                $table->index("TypeID", "TypeID");
                $table->foreign("PresentationTrackChairScoreType", "TypeID", "ID", ["onDelete" => "CASCADE"], "FK_PresentationTrackChairScore_Type");

                $table->integer("MemberID" );
                $table->index("MemberID", "MemberID");
                $table->foreign("Member", "MemberID", "ID", ["onDelete" => "CASCADE"], "FK_PresentationTrackChairScore_Member");

                $table->integer("PresentationID" );
                $table->index("PresentationID", "PresentationID");
                $table->foreign("Presentation", "PresentationID", "ID", ["onDelete" => "CASCADE"], "FK_PresentationTrackChairScore_Presentation");

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $schema->dropTable("PresentationTrackChairScore");
    }
}

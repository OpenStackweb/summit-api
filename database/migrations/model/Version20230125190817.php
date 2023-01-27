<?php namespace Database\Migrations\Model;
/**
 * Copyright 2023 OpenStack Foundation
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
 * Class Version20230125190817
 * @package Database\Migrations\Model
 */
final class Version20230125190817 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$schema->hasTable("SummitSubmissionInvitation")) {
            $builder->create('SummitSubmissionInvitation', function (Table $table) {

                $table->integer("ID", true, true);
                $table->primary("ID");
                $table->string('ClassName')->setDefault("SummitSubmissionInvitation");
                $table->index("ClassName", "ClassName");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                // Fields

                $table->string('Email', 255)->setNotnull(true);
                $table->string('FirstName', 255)->setNotnull(false)->setDefault('NULL');
                $table->string('LastName', 255)->setNotnull(false)->setDefault('NULL');
                $table->timestamp('SentDate')->setNotnull(false);
                $table->string('OTP',50)->setNotnull(false)->setDefault('NULL');

                // FK
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"], 'FK_SummitSubmissionInvitation_Summit');

                // FK
                $table->integer("SpeakerID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SpeakerID", "SpeakerID");
                $table->foreign("PresentationSpeaker", "SpeakerID", "ID", ["onDelete" => "CASCADE"], 'FK_SummitSubmissionInvitation_Speaker');

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable("SummitSubmissionInvitation")) {
            $schema->dropTable('SummitSubmissionInvitation');
        }
    }
}

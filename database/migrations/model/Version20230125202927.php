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
 * Class Version20230125202927
 * @package Database\Migrations\Model
 */
final class Version20230125202927 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$schema->hasTable("SummitSubmissionInvitation_Tags")) {
            $builder->create('SummitSubmissionInvitation_Tags', function (Table $table) {

                $table->bigInteger("ID", true, true);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                $table->integer("SummitSubmissionInvitationID", false, true)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitSubmissionInvitationID", "SummitSubmissionInvitationID");
                $table->foreign("SummitSubmissionInvitation", "SummitSubmissionInvitationID", "ID", ["onDelete" => "CASCADE"], "FK_SummitSubmissionInvitation_Tags_Invitation");

                $table->integer("TagID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("TagID", "TagID");
                $table->foreign("Tag", "TagID", "ID", ["onDelete" => "CASCADE"], "FK_SummitSubmissionInvitation_Tags_Tag");

                $table->unique(['SummitSubmissionInvitationID', 'TagID']);

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable("SummitSubmissionInvitation_Tags")) {
            $schema->dropTable('SummitSubmissionInvitation_Tags');
        }
    }
}

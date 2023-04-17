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

class Version20230327152144 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$schema->hasTable("SummitOrderExtraQuestionType_SummitTicketType")) {
            $builder->create("SummitOrderExtraQuestionType_SummitTicketType", function (Table $table) {

                $table->bigInteger("ID", true, true);
                $table->primary("ID");
                $table->timestamp("Created")->setDefault('CURRENT_TIMESTAMP');
                $table->timestamp("LastEdited")->setDefault('CURRENT_TIMESTAMP');

                $table->integer("SummitOrderExtraQuestionTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitOrderExtraQuestionTypeID", "SummitOrderExtraQuestionTypeID");
                $table->foreign("SummitOrderExtraQuestionType", "SummitOrderExtraQuestionTypeID", "ID", ["onDelete" => "CASCADE"], "FK_OrderExtraQType_TicketType_OrderExtraQType");

                $table->integer("SummitTicketTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitTicketTypeID", "SummitTicketTypeID");
                $table->foreign("SummitTicketType", "SummitTicketTypeID", "ID", ["onDelete" => "CASCADE"], "FK_OrderExtraQType_TicketType_TicketType");

                $table->unique(["SummitOrderExtraQuestionTypeID", "SummitTicketTypeID"], "QuestionTypeID_TicketTypeID");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable("SummitOrderExtraQuestionType_SummitTicketType")) {
            $schema->dropTable("SummitOrderExtraQuestionType_SummitTicketType");
        }
    }
}

<?php namespace Database\Migrations\Model;
/**
 * Copyright 2024 OpenStack Foundation
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
use LaravelDoctrine\Migrations\Schema\Table;
/**
 * Class Version20240226193210
 * @package Database\Migrations\Model
 */
final class Version20240226193210 extends AbstractMigration
{
    use CreateTableTrait;

    const TableName = 'SummitTaxRefund';
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        self::createTable($schema, self::TableName, function(Table $table){

            $table->decimal("RefundedAmount", 9, 2)->setDefault('0.00');

            // FK
            $table->integer("SummitRefundRequestID", false, false)->setNotnull(false)->setDefault(null);
            $table->index("SummitRefundRequestID", "SummitRefundRequestID");
            $table->foreign("SummitRefundRequest", "SummitRefundRequestID", "ID", ["onDelete" => "CASCADE"]);

            // FK
            $table->integer("SummitTaxTypeID", false, false)->setNotnull(false)->setDefault(null);
            $table->index("SummitTaxTypeID", "SummitTaxTypeID");
            $table->foreign("SummitTaxType", "SummitTaxTypeID", "ID", ["onDelete" => "CASCADE"]);

            // IDX
            $table->unique(['SummitRefundRequestID','SummitTaxTypeID'], 'IDX_RefundRequest_TaxType');
        });

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $schema->dropTable(self::TableName);
    }
}

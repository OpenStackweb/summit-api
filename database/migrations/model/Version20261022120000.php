<?php namespace Database\Migrations\Model;
/**
 * Copyright 2026 OpenStack Foundation
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

/**
 * Class Version20261022120000
 *
 * - IDX_SummitOrder_OwnerEmail_Owner supports DoctrineSummitOrderRepository::getAllByOwnerEmailAndOwnerNotSet()
 *   (WHERE OwnerEmail = ? AND OwnerID IS NULL); OwnerEmail is only the third column of
 *   IDX_SummitOrder_OwnerFields_Summit, so the optimizer scanned every ownerless order through the OwnerID index.
 * - IDX_SummitOrder_Hash and IDX_SummitOrder_PaymentGatewayCartId support getByHashLockExclusive() and
 *   getByPaymentGatewayCartIdExclusiveLock(); without them the PESSIMISTIC_WRITE lookups scan (and lock) the whole table.
 *
 * @package Database\Migrations\Model
 */
class Version20261022120000 extends AbstractMigration
{
    private const Indexes = [
        'IDX_SummitOrder_OwnerEmail_Owner' => '`OwnerEmail`, `OwnerID`',
        'IDX_SummitOrder_Hash' => '`Hash`',
        'IDX_SummitOrder_PaymentGatewayCartId' => '`PaymentGatewayCartId`',
    ];

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $db = DB::connection("model")->getDatabaseName();
        foreach (self::Indexes as $name => $columns) {
            if (!DBHelpers::existsIDX($db, 'SummitOrder', $name)) {
                $this->addSql("ALTER TABLE `SummitOrder` ADD INDEX `{$name}` ({$columns}) USING BTREE, ALGORITHM=INPLACE, LOCK=NONE;");
            }
        }
    }

    public function down(Schema $schema): void
    {
        $db = DB::connection("model")->getDatabaseName();
        foreach (self::Indexes as $name => $columns) {
            if (DBHelpers::existsIDX($db, 'SummitOrder', $name)) {
                $this->addSql("ALTER TABLE `SummitOrder` DROP INDEX `{$name}`;");
            }
        }
    }
}

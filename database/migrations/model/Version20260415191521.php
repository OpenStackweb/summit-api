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

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Class Version20260415191521
 * @package Database\Migrations\Model
 *
 * Creates the SummitPromoCodeMemberReservation table used to atomically
 * track per-member QuantityPerAccount usage for domain-authorized promo
 * codes. Fixes the TOCTOU race documented in smarcet's PR #530. A
 * companion migration (Version20260415191522) backfills the table from
 * existing committed tickets — it runs after this one so the CREATE
 * TABLE has fully committed before the INSERT executes.
 */
final class Version20260415191521 extends AbstractMigration
{
    private const TableName = 'SummitPromoCodeMemberReservation';

    public function getDescription(): string
    {
        return 'Create SummitPromoCodeMemberReservation counter table.';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable(self::TableName)) {
            $builder->create(self::TableName, function (Table $table) {
                $table->integer('ID', true, false);
                $table->primary('ID');

                $table->timestamp('Created')->setNotnull(true);
                $table->timestamp('LastEdited')->setNotnull(true);

                $table->integer('QtyUsed')->setNotnull(true)->setDefault(0);

                $table->integer('PromoCodeID', false, false)->setNotnull(true);
                $table->index('PromoCodeID', 'PromoCodeID');
                $table->foreign(
                    'SummitRegistrationPromoCode',
                    'PromoCodeID',
                    'ID',
                    ['onDelete' => 'CASCADE'],
                    'FK_PromoCodeMemberReservation_PromoCode'
                );

                $table->integer('MemberID', false, false)->setNotnull(true);
                $table->index('MemberID', 'MemberID');
                $table->foreign(
                    'Member',
                    'MemberID',
                    'ID',
                    ['onDelete' => 'CASCADE'],
                    'FK_PromoCodeMemberReservation_Member'
                );

                $table->unique(['PromoCodeID', 'MemberID'], 'UQ_PromoCode_Member');
            });
        }
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($builder->hasTable(self::TableName)) {
            $builder->dropIfExists(self::TableName);
        }
    }
}

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
 * Class Version20260401150000
 * @package Database\Migrations\Model
 *
 * Creates joined tables for DomainAuthorizedSummitRegistrationDiscountCode and
 * DomainAuthorizedSummitRegistrationPromoCode, adds WithPromoCode to the
 * SummitTicketType Audience ENUM, and adds AutoApply columns to the four
 * existing email-linked subtype joined tables (Member/Speaker promo/discount).
 */
final class Version20260401150000 extends AbstractMigration
{
    private const AutoApplyTables = [
        'MemberSummitRegistrationPromoCode',
        'MemberSummitRegistrationDiscountCode',
        'SpeakerSummitRegistrationPromoCode',
        'SpeakerSummitRegistrationDiscountCode',
    ];

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        // 1. Create DomainAuthorizedSummitRegistrationDiscountCode joined table
        if (!$builder->hasTable('DomainAuthorizedSummitRegistrationDiscountCode')) {
            $builder->create('DomainAuthorizedSummitRegistrationDiscountCode', function (Table $table) {
                $table->integer('ID', false, false)->setNotnull(true);
                $table->primary('ID');
                $table->json('AllowedEmailDomains')->setNotnull(false)->setDefault(null);
                $table->integer('QuantityPerAccount')->setNotnull(true)->setDefault(0);
                $table->boolean('AutoApply')->setNotnull(true)->setDefault(false);
                $table->foreign(
                    'SummitRegistrationPromoCode',
                    'ID',
                    'ID',
                    ['onDelete' => 'CASCADE'],
                    'FK_DomainAuthDiscountCode_PromoCode'
                );
            });
        }

        // 2. Create DomainAuthorizedSummitRegistrationPromoCode joined table
        if (!$builder->hasTable('DomainAuthorizedSummitRegistrationPromoCode')) {
            $builder->create('DomainAuthorizedSummitRegistrationPromoCode', function (Table $table) {
                $table->integer('ID', false, false)->setNotnull(true);
                $table->primary('ID');
                $table->json('AllowedEmailDomains')->setNotnull(false)->setDefault(null);
                $table->integer('QuantityPerAccount')->setNotnull(true)->setDefault(0);
                $table->boolean('AutoApply')->setNotnull(true)->setDefault(false);
                $table->foreign(
                    'SummitRegistrationPromoCode',
                    'ID',
                    'ID',
                    ['onDelete' => 'CASCADE'],
                    'FK_DomainAuthPromoCode_PromoCode'
                );
            });
        }

        // 3. Widen the ClassName discriminator ENUM to include the two new subtypes
        //    (Doctrine Schema does not support MySQL ENUM — raw SQL is the established pattern;
        //    see Version20231208172204.)
        if ($builder->hasTable('SummitRegistrationPromoCode')) {
            $this->addSql("ALTER TABLE SummitRegistrationPromoCode MODIFY ClassName ENUM(
                'SummitRegistrationPromoCode',
                'MemberSummitRegistrationPromoCode',
                'SponsorSummitRegistrationPromoCode',
                'SpeakerSummitRegistrationPromoCode',
                'SummitRegistrationDiscountCode',
                'MemberSummitRegistrationDiscountCode',
                'SponsorSummitRegistrationDiscountCode',
                'SpeakerSummitRegistrationDiscountCode',
                'SpeakersSummitRegistrationPromoCode',
                'SpeakersRegistrationDiscountCode',
                'PrePaidSummitRegistrationPromoCode',
                'PrePaidSummitRegistrationDiscountCode',
                'DomainAuthorizedSummitRegistrationDiscountCode',
                'DomainAuthorizedSummitRegistrationPromoCode'
            ) DEFAULT 'SummitRegistrationPromoCode'");
        }

        // 4. Add WithPromoCode to SummitTicketType Audience ENUM
        if ($builder->hasTable('SummitTicketType')) {
            $this->addSql("ALTER TABLE SummitTicketType MODIFY Audience ENUM('All', 'WithInvitation', 'WithoutInvitation', 'WithPromoCode') NOT NULL DEFAULT 'All'");
        }

        // 5. Add AutoApply column to existing email-linked subtype joined tables
        foreach (self::AutoApplyTables as $tableName) {
            if ($builder->hasTable($tableName) && !$builder->hasColumn($tableName, 'AutoApply')) {
                $builder->table($tableName, function (Table $table) {
                    $table->boolean('AutoApply')->setNotnull(true)->setDefault(false);
                });
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);

        // 1. Drop AutoApply columns from existing email-linked subtype tables
        foreach (self::AutoApplyTables as $tableName) {
            if ($builder->hasTable($tableName) && $builder->hasColumn($tableName, 'AutoApply')) {
                $builder->table($tableName, function (Table $table) {
                    $table->dropColumn('AutoApply');
                });
            }
        }

        // 2. Guard against orphaned WithPromoCode values before narrowing the ENUM
        if ($builder->hasTable('SummitTicketType')) {
            $this->addSql("UPDATE SummitTicketType SET Audience = 'All' WHERE Audience = 'WithPromoCode'");

            // 3. Revert SummitTicketType Audience ENUM
            $this->addSql("ALTER TABLE SummitTicketType MODIFY Audience ENUM('All', 'WithInvitation', 'WithoutInvitation') NOT NULL DEFAULT 'All'");
        }

        // 4. Remap domain-authorized rows to base types BEFORE dropping joined tables.
        //    (SummitAttendeeTicket.PromoCodeID cascades on delete, so DELETE would destroy
        //    ticket history. Remap must happen before DROP TABLE because DROP TABLE causes
        //    an implicit commit in MySQL — if a later step fails, the discriminator would
        //    still point at non-existent joined tables and every promo-code query would
        //    crash with a join error.)
        if ($builder->hasTable('SummitRegistrationPromoCode')) {
            $this->addSql("UPDATE SummitRegistrationPromoCode
                SET ClassName = CASE ClassName
                    WHEN 'DomainAuthorizedSummitRegistrationDiscountCode' THEN 'SummitRegistrationDiscountCode'
                    WHEN 'DomainAuthorizedSummitRegistrationPromoCode' THEN 'SummitRegistrationPromoCode'
                END
                WHERE ClassName IN (
                    'DomainAuthorizedSummitRegistrationDiscountCode',
                    'DomainAuthorizedSummitRegistrationPromoCode'
                )");

            // 5. Revert the ClassName discriminator ENUM to the original 12 values
            $this->addSql("ALTER TABLE SummitRegistrationPromoCode MODIFY ClassName ENUM(
                'SummitRegistrationPromoCode',
                'MemberSummitRegistrationPromoCode',
                'SponsorSummitRegistrationPromoCode',
                'SpeakerSummitRegistrationPromoCode',
                'SummitRegistrationDiscountCode',
                'MemberSummitRegistrationDiscountCode',
                'SponsorSummitRegistrationDiscountCode',
                'SpeakerSummitRegistrationDiscountCode',
                'SpeakersSummitRegistrationPromoCode',
                'SpeakersRegistrationDiscountCode',
                'PrePaidSummitRegistrationPromoCode',
                'PrePaidSummitRegistrationDiscountCode'
            ) DEFAULT 'SummitRegistrationPromoCode'");
        }

        // 6. Drop the new joined tables LAST
        if ($builder->hasTable('DomainAuthorizedSummitRegistrationPromoCode')) {
            $builder->dropIfExists('DomainAuthorizedSummitRegistrationPromoCode');
        }
        if ($builder->hasTable('DomainAuthorizedSummitRegistrationDiscountCode')) {
            $builder->dropIfExists('DomainAuthorizedSummitRegistrationDiscountCode');
        }
    }
}

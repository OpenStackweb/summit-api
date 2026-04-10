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
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // 1. Create DomainAuthorizedSummitRegistrationDiscountCode joined table
        $this->addSql("CREATE TABLE DomainAuthorizedSummitRegistrationDiscountCode (
            ID INT NOT NULL,
            AllowedEmailDomains JSON DEFAULT NULL,
            QuantityPerAccount INT NOT NULL DEFAULT 0,
            AutoApply TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (ID),
            CONSTRAINT FK_DomainAuthDiscountCode_PromoCode FOREIGN KEY (ID) REFERENCES SummitRegistrationPromoCode (ID) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 2. Create DomainAuthorizedSummitRegistrationPromoCode joined table
        $this->addSql("CREATE TABLE DomainAuthorizedSummitRegistrationPromoCode (
            ID INT NOT NULL,
            AllowedEmailDomains JSON DEFAULT NULL,
            QuantityPerAccount INT NOT NULL DEFAULT 0,
            AutoApply TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (ID),
            CONSTRAINT FK_DomainAuthPromoCode_PromoCode FOREIGN KEY (ID) REFERENCES SummitRegistrationPromoCode (ID) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 3. Widen the ClassName discriminator ENUM to include the two new subtypes
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

        // 4. Add WithPromoCode to SummitTicketType Audience ENUM
        $this->addSql("ALTER TABLE SummitTicketType MODIFY Audience ENUM('All', 'WithInvitation', 'WithoutInvitation', 'WithPromoCode') NOT NULL DEFAULT 'All'");

        // 5. Add AutoApply column to existing email-linked subtype joined tables
        $this->addSql("ALTER TABLE MemberSummitRegistrationPromoCode ADD COLUMN AutoApply TINYINT(1) NOT NULL DEFAULT 0");
        $this->addSql("ALTER TABLE MemberSummitRegistrationDiscountCode ADD COLUMN AutoApply TINYINT(1) NOT NULL DEFAULT 0");
        $this->addSql("ALTER TABLE SpeakerSummitRegistrationPromoCode ADD COLUMN AutoApply TINYINT(1) NOT NULL DEFAULT 0");
        $this->addSql("ALTER TABLE SpeakerSummitRegistrationDiscountCode ADD COLUMN AutoApply TINYINT(1) NOT NULL DEFAULT 0");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // 1. Drop AutoApply columns from existing email-linked subtype tables
        $this->addSql("ALTER TABLE SpeakerSummitRegistrationDiscountCode DROP COLUMN AutoApply");
        $this->addSql("ALTER TABLE SpeakerSummitRegistrationPromoCode DROP COLUMN AutoApply");
        $this->addSql("ALTER TABLE MemberSummitRegistrationDiscountCode DROP COLUMN AutoApply");
        $this->addSql("ALTER TABLE MemberSummitRegistrationPromoCode DROP COLUMN AutoApply");

        // 2. Guard against orphaned WithPromoCode values before narrowing the ENUM
        $this->addSql("UPDATE SummitTicketType SET Audience = 'All' WHERE Audience = 'WithPromoCode'");

        // 3. Revert SummitTicketType Audience ENUM
        $this->addSql("ALTER TABLE SummitTicketType MODIFY Audience ENUM('All', 'WithInvitation', 'WithoutInvitation') NOT NULL DEFAULT 'All'");

        // 4. Drop new joined tables
        $this->addSql("DROP TABLE IF EXISTS DomainAuthorizedSummitRegistrationPromoCode");
        $this->addSql("DROP TABLE IF EXISTS DomainAuthorizedSummitRegistrationDiscountCode");

        // 4b. Delete orphaned base-table rows before narrowing the ENUM
        $this->addSql("DELETE FROM SummitRegistrationPromoCode WHERE ClassName IN (
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
}

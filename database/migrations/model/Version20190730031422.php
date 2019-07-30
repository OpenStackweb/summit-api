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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;
/**
 * Class Version20190730031422
 * @package Database\Migrations\Model
 */
final class Version20190730031422 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        // access level per summit

        if (!$builder->hasTable("SummitAccessLevelType")) {
            $builder->create("SummitAccessLevelType", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string("Name")->setNotnull(true);
                $table->string("Description")->setNotnull(false);
                $table->boolean("IsDefault")->setDefault(false);
                $table->text("TemplateContent");
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        // badge features
        if (!$builder->hasTable("SummitBadgeFeatureType")) {
            $builder->create("SummitBadgeFeatureType", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string("Name")->setNotnull(true);
                $table->string("Description")->setNotnull(false);
                $table->text("TemplateContent");
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        // badge types per summit
        if (!$builder->hasTable("SummitBadgeType")) {
            $builder->create("SummitBadgeType", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string("Name")->setNotnull(true);
                $table->string("Description")->setNotnull(false);
                $table->text("TemplateContent");
                $table->boolean("IsDefault")->setDefault(false);
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);
                $table->integer("FileID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("FileID", "FileID");
                $table->foreign("File", "FileID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        if (!$builder->hasTable("SummitBadgeType_AccessLevels")) {
            $builder->create("SummitBadgeType_AccessLevels", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->integer("SummitBadgeTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitBadgeTypeID", "SummitBadgeTypeID");
                //$table->foreign("SummitBadgeType", "SummitBadgeTypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitAccessLevelTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitAccessLevelTypeID", "SummitAccessLevelTypeID");
                //$table->foreign("SummitAccessLevelType", "SummitAccessLevelTypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->unique(['SummitBadgeTypeID', 'SummitAccessLevelTypeID']);
            });
        }

        // tax per summit
        if (!$builder->hasTable("SummitTaxType")) {
            $builder->create("SummitTaxType", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string("Name");
                $table->string("TaxID");
                $table->decimal("Rate", 9, 2)->setDefault('0.00');
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        // refund policies
        if (!$builder->hasTable("SummitRefundPolicyType")) {
            $builder->create("SummitRefundPolicyType", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string("Name");
                $table->integer("UntilXDaysBeforeEventStarts")->setDefault(0);
                $table->decimal("RefundRate", 9, 2)->setDefault('0.00');
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        // update to ticket types
        if ($builder->hasTable("SummitTicketType") && !$builder->hasColumn("SummitTicketType", "Cost")) {
            $builder->table("SummitTicketType", function (Table $table) {
                $table->decimal("Cost", 9, 2)->setDefault('0.00');
                $table->string("Currency", 3)->setDefault('USD');
                $table->integer("QuantityToSell")->setDefault(0);
                $table->integer("QuantitySold")->setDefault(0);
                $table->integer("MaxQuantityToSellPerOrder")->setDefault(0);
                $table->timestamp('SaleStartDate')->setNotnull(false);
                $table->timestamp('SaleEndDate')->setNotnull(false);
            });
        }

        // taxes associated per tix type
        if (!$builder->hasTable("SummitTicketType_Taxes")) {
            $builder->create("SummitTicketType_Taxes", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->integer("SummitTicketTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitTicketTypeID", "SummitTicketTypeID");
                //$table->foreign("SummitTicketType", "TicketTypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitTaxTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitTaxTypeID", "SummitTaxTypeID");
                //$table->foreign("SummitTaxType", "TaxTypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->unique(['SummitTicketTypeID', 'SummitTaxTypeID']);
            });
        }

        // promo codes and discount codes


        // update to promo codes with new fields
        if ($builder->hasTable("SummitRegistrationPromoCode") && !$builder->hasColumn("SummitRegistrationPromoCode", "QuantityAvailable")) {
            $builder->table("SummitRegistrationPromoCode", function (Table $table) {
                $table->integer("QuantityAvailable")->setDefault(0)->setNotnull(false);
                $table->integer("QuantityUsed")->setDefault(0)->setNotnull(false);
                $table->timestamp('ValidSinceDate')->setNotnull(false);
                $table->timestamp('ValidUntilDate')->setNotnull(false);
                $table->integer("BadgeTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("BadgeTypeID", "BadgeTypeID");
                $table->foreign("SummitBadgeType", "BadgeTypeID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        // features per promo code
        if (!$builder->hasTable("SummitRegistrationPromoCode_BadgeFeatures")) {
            $builder->create("SummitRegistrationPromoCode_BadgeFeatures", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->integer("SummitRegistrationPromoCodeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitRegistrationPromoCodeID", "SummitRegistrationPromoCodeID");
                //$table->foreign("SummitRegistrationPromoCode", "SummitRegistrationPromoCodeID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitBadgeFeatureTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitBadgeFeatureTypeID", "SummitBadgeFeatureTypeID");
                //$table->foreign("SummitBadgeFeatureType", "SummitBadgeFeatureTypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->unique(['SummitRegistrationPromoCodeID', 'SummitBadgeFeatureTypeID']);
            });
        }

        // allowed tix types per promo code
        if (!$builder->hasTable("SummitRegistrationPromoCode_AllowedTicketTypes")) {

            $builder->create("SummitRegistrationPromoCode_AllowedTicketTypes", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->integer("SummitRegistrationPromoCodeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitRegistrationPromoCodeID", "SummitRegistrationPromoCodeID");
                //$table->foreign("SummitRegistrationPromoCode", "SummitRegistrationPromoCodeID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitTicketTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitTicketTypeID", "SummitTicketTypeID");
                //$table->foreign("SummitTicketType", "SummitTicketTypeID", "ID", ["onDelete" => "CASCADE"]);
                $table->unique(['SummitRegistrationPromoCodeID', 'SummitTicketTypeID']);
            });
        }

        // discount codes
        if (!$builder->hasTable("SummitRegistrationDiscountCode")) {

            $builder->create("SummitRegistrationDiscountCode", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->decimal("DiscountRate", 9, 2)->setDefault('0.00')->setNotnull(true);
                $table->decimal("DiscountAmount", 9, 2)->setDefault('0.00')->setNotnull(true);

                $table->foreign("SummitRegistrationPromoCode", "ID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        if (!$builder->hasTable("MemberSummitRegistrationDiscountCode")) {
            $builder->create("MemberSummitRegistrationDiscountCode", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->integer("OwnerID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("OwnerID", "OwnerID");
                $table->foreign("Member", "OwnerID", "ID", ["onDelete" => "CASCADE"]);
                $table->string("FirstName");
                $table->string("LastName");
                $table->string("Email");
                $table->string("Type");
                $table->foreign("SummitRegistrationPromoCode", "ID", "ID", ["onDelete" => "CASCADE"]);
            });

        }

        if (!$builder->hasTable("SpeakerSummitRegistrationDiscountCode")) {
            $builder->create("SpeakerSummitRegistrationDiscountCode", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->integer("SpeakerID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SpeakerID", "SpeakerID");
                $table->foreign("PresentationSpeaker", "SpeakerID", "ID", ["onDelete" => "CASCADE"]);
                $table->string("Type");
                $table->foreign("SummitRegistrationPromoCode", "ID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        if (!$builder->hasTable("SponsorSummitRegistrationDiscountCode")) {
            $builder->create("SponsorSummitRegistrationDiscountCode", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->integer("SponsorID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SponsorID", "SponsorID");
                $table->foreign("Company", "SponsorID", "ID", ["onDelete" => "CASCADE"]);
                $table->foreign("SummitRegistrationPromoCode", "ID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        // allowed tix types for discount codes

        if (!$builder->hasTable("SummitRegistrationDiscountCode_AllowedTicketTypes")) {

            $builder->create("SummitRegistrationDiscountCode_AllowedTicketTypes", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->decimal("DiscountRate", 9, 2)->setDefault('0.00');
                $table->decimal("DiscountAmount", 9, 2)->setDefault('0.00');

                $table->integer("SummitRegistrationDiscountCodeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitRegistrationDiscountCodeID", "SummitRegistrationDiscountCodeID");
                //$table->foreign("SummitRegistrationDiscountCode", "SummitRegistrationDiscountCodeID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitTicketTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitTicketTypeID", "SummitTicketTypeID");
                //$table->foreign("SummitTicketType", "SummitTicketTypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->unique(['SummitRegistrationDiscountCodeID', 'SummitTicketTypeID']);
            });
        }

        if (!$builder->hasTable("SummitOrder")) {
            $builder->create("SummitOrder", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);

                $table->string("Status");
                $table->string("PaymentMethod");
                $table->string("Number");
                $table->index("Number", "Number");
                $table->unique(["SummitID", "Number"], "SummitID_Number");
                $table->string("QRCode")->setDefault("")->setNotnull(false);
                $table->index("QRCode", "QRCode");

                // owner
                $table->string("OwnerFirstName");
                $table->string("OwnerSurname");
                $table->string("OwnerEmail", 100);
                $table->string("OwnerCompany");

                $table->integer("CompanyID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("CompanyID", "CompanyID");
                $table->foreign("Company", "CompanyID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("OwnerID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("OwnerID", "OwnerID");
                $table->foreign("Member", "OwnerID", "ID", ["onDelete" => "CASCADE"]);
                $table->timestamp('DisclaimerAcceptedDate')->setNotnull(false);
                // billing address
                $table->string("BillingAddress1", 100)->setNotnull(false);
                $table->string("BillingAddress2", 100)->setNotnull(false);
                $table->string("BillingAddressZipCode", 50)->setNotnull(false);
                $table->string("BillingAddressCity", 50)->setNotnull(false);
                $table->string("BillingAddressState", 50)->setNotnull(false);
                $table->string("BillingAddressCountryISOCode", 3)->setNotnull(false);

                //payment gateway

                $table->timestamp('ApprovedPaymentDate')->setNotnull(false);
                $table->string("LastError")->setNotnull(false);
                $table->text("PaymentGatewayClientToken")->setNotnull(false);
                $table->string("PaymentGatewayCartId", 512)->setNotnull(false);
                $table->index("PaymentGatewayCartId", "PaymentGatewayCartId");

                $table->string("Hash", 255)->setNotnull(false);
                $table->unique("Hash", "Hash");
                $table->timestamp('HashCreationDate')->setNotnull(false);
            });
        }

        if ($builder->hasTable("SummitAttendeeTicket") && !$builder->hasColumn("SummitAttendeeTicket","OrderID")) {
            $builder->table("SummitAttendeeTicket", function (Table $table) {

                $table->integer("OrderID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("OrderID", "OrderID");
                $table->foreign("SummitOrder", "OrderID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("PromoCodeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("PromoCodeID", "PromoCodeID");
                $table->foreign("SummitRegistrationPromoCode", "PromoCodeID", "ID", ["onDelete" => "CASCADE"]);

                $table->foreign("SummitAttendee", "OwnerID", "ID", ["onDelete" => "CASCADE"]);

                $table->decimal("RawCost", 9, 2)->setDefault('0.00');
                $table->decimal("Discount", 9, 2)->setDefault('0.00');
                $table->string("Status");
                $table->string("Number")->setNotnull(false);
                $table->unique("Number", "Number");
                $table->decimal("RefundedAmount", 9, 2)->setDefault('0.00');
                $table->string("Currency", 3)->setDefault('USD');

                $table->string("QRCode")->setDefault("")->setNotnull(false);
                $table->index("QRCode", "QRCode");

                $table->string("Hash", 255)->setNotnull(false);
                $table->unique("Hash", "Hash");
                $table->timestamp('HashCreationDate')->setNotnull(false);

            });

        }

        if (!$builder->hasTable("SummitAttendeeTicket_Taxes")) {
            $builder->create("SummitAttendeeTicket_Taxes", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->integer("SummitAttendeeTicketID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitAttendeeTicketID", "SummitAttendeeTicketID");
                //$table->foreign("SummitAttendeeTicket", "TicketID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitTaxTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitTaxTypeID", "SummitTaxTypeID");
                //$table->foreign("SummitTaxType", "TaxTypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->decimal("Amount", 9, 2)->setDefault('0.00');

                $table->unique(['SummitAttendeeTicketID', 'SummitTaxTypeID']);
            });
        }

        if ($builder->hasTable("SummitAttendee") && !$builder->hasColumn("SummitAttendee", "FirstName")) {
            $builder->table("SummitAttendee", function (Table $table) {
                $table->string("FirstName", 255)->setNotnull(false);
                $table->string("Surname", 255)->setNotnull(false);
                $table->string("Email", 100)->setNotnull(false);
                $table->index("Email", "Email");
                $table->timestamp('DisclaimerAcceptedDate')->setNotnull(false);
            });
        }

        if (!$builder->hasTable("SummitAttendeeBadge")) {
            $builder->create("SummitAttendeeBadge", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                $table->timestamp('PrintDate')->setNotnull(false);
                $table->boolean('IsVoid')->setDefault(false);
                $table->string("QRCode")->setDefault("")->setNotnull(false);
                $table->index("QRCode", "QRCode");

                $table->integer("TicketID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("TicketID", "TicketID");
                $table->foreign("SummitAttendeeTicket", "TicketID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("BadgeTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("BadgeTypeID", "BadgeTypeID");
                $table->foreign("SummitBadgeType", "BadgeTypeID", "ID", ["onDelete" => "CASCADE"]);

            });
        }

        if (!$builder->hasTable("SummitAttendeeBadge_Features")) {
            $builder->create("SummitAttendeeBadge_Features", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->integer("SummitAttendeeBadgeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitAttendeeBadgeID", "SummitAttendeeBadgeID");
                //$table->foreign("SummitAttendeeBadge", "BadgeID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitBadgeFeatureTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitBadgeFeatureTypeID", "SummitBadgeFeatureTypeID");
                //$table->foreign("SummitBadgeFeatureType", "SummitBadgeFeatureTypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->unique(['SummitAttendeeBadgeID', 'SummitBadgeFeatureTypeID']);
            });
        }


        // order extra question types

        if (!$builder->hasTable("SummitOrderExtraQuestionType")) {

            $builder->create("SummitOrderExtraQuestionType", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);

                $table->string("Name", 100);
                $table->string("Type");
                $table->string("Label");
                $table->integer("Order")->setDefault(0);

                $table->boolean("Mandatory")->setDefault(false);
                $table->string("Usage", 50);
                $table->string("Placeholder", 50);
                $table->boolean("Printable")->setDefault(false);

            });
        }

        if (!$builder->hasTable("SummitOrderExtraQuestionValue")) {
            $builder->create("SummitOrderExtraQuestionValue", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                $table->integer("QuestionID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("QuestionID", "QuestionID");
                $table->foreign("SummitOrderExtraQuestionType", "QuestionID", "ID", ["onDelete" => "CASCADE"]);

                $table->string("Value");
                $table->string("Label");
                $table->integer("Order")->setDefault(0);
            });
        }


        if (!$builder->hasTable("SummitOrderExtraQuestionAnswer")) {

            $builder->create("SummitOrderExtraQuestionAnswer", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                $table->text("Value");

                $table->integer("QuestionID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("QuestionID", "QuestionID");
                $table->foreign("SummitOrderExtraQuestionType", "QuestionID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("OrderID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("OrderID", "OrderID");
                $table->foreign("SummitOrder", "OrderID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitAttendeeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitAttendeeID", "SummitAttendeeID");
                $table->foreign("SummitAttendee", "SummitAttendeeID", "ID", ["onDelete" => "CASCADE"]);

            });
        }

        // sponsors users

        if (!$builder->hasTable("Sponsor_Users")) {

            $builder->create("Sponsor_Users", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                $table->integer("SponsorID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SponsorID", "SponsorID");
                //$table->foreign("Sponsor", "SponsorID", "ID", ["onDelete" => "CASCADE"]);


                $table->integer("MemberID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("MemberID", "MemberID");
                //$table->foreign("Member", "UserID", "ID", ["onDelete" => "CASCADE"]);

                $table->unique(['SponsorID', 'MemberID']);

            });
        }

        // sponsors badge scans


        if (!$builder->hasTable("SponsorBadgeScan")) {

            $builder->create("SponsorBadgeScan", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                $table->integer("SponsorID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SponsorID", "SponsorID");
                $table->foreign("Sponsor", "SponsorID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("UserID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("UserID", "UserID");
                $table->foreign("Member", "UserID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("BadgeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("BadgeID", "BadgeID");
                $table->foreign("SummitAttendeeBadge", "BadgeID", "ID", ["onDelete" => "CASCADE"]);

                $table->string("QRCode");

            });
        }

        if ($builder->hasTable("Summit") && !$builder->hasColumn("Summit", "ReAssignTicketTillDate")) {
            $builder->table("Summit", function (Table $table) {
                $table->timestamp('ReAssignTicketTillDate')->setNotnull(false);
                $table->text('RegistrationDisclaimerContent')->setNotnull(false);
                $table->boolean('RegistrationDisclaimerMandatory')->setNotnull(false)->setDefault(false);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}

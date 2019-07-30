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
class Version20190801211505 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
ALTER TABLE SummitRegistrationPromoCode MODIFY ClassName 
enum(
'SummitRegistrationPromoCode',
'MemberSummitRegistrationPromoCode',
'SponsorSummitRegistrationPromoCode',
'SpeakerSummitRegistrationPromoCode',
'SummitRegistrationDiscountCode',
'MemberSummitRegistrationDiscountCode',
'SpeakerSummitRegistrationDiscountCode',
'SponsorSummitRegistrationDiscountCode'
) default 'SummitRegistrationPromoCode' null;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitOrder MODIFY Status
enum(
'Reserved',
'Cancelled',
'RefundRequested',
'Refunded',
'Confirmed',
'Paid',
'Error'
) default 'Reserved' null;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitOrder MODIFY PaymentMethod
enum(
'Online',
'Offline'
) default 'Online' null;
SQL;

        $this->addSql($sql);


        $sql = <<<SQL
ALTER TABLE SummitAttendeeTicket MODIFY Status
enum(
'Reserved',
'Cancelled',
'RefundRequested',
'Refunded',
'Confirmed',
'Paid',
'Error'
) default 'Reserved' null;
SQL;

        $this->addSql($sql);


        $sql = <<<SQL
ALTER TABLE SummitOrderExtraQuestionType MODIFY `Type`
enum(
'TextArea',
'Text',
'CheckBox',
'RadioButton',
'ComboBox',
'CheckBoxList',
'RadioButtonList'
) default 'Text' null;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitOrderExtraQuestionType MODIFY `Usage`
enum(
'Order',
'Ticket',
'Both'
) default 'Order' null;
SQL;

        $this->addSql($sql);

        // promo codes

        $sql = <<<SQL
ALTER TABLE MemberSummitRegistrationDiscountCode MODIFY Type
enum(
'VIP','ATC','MEDIA ANALYST','SPONSOR'
) default 'VIP' null;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SpeakerSummitRegistrationDiscountCode MODIFY Type
enum(
'ACCEPTED','ALTERNATE'
) default 'ALTERNATE' null;
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}

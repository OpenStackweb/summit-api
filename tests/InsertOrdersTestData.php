<?php namespace Tests;
/**
 * Copyright 2021 OpenStack Foundation
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

use Illuminate\Support\Facades\DB;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitBadgeType;
use models\summit\SummitOrder;
use models\summit\SummitTicketType;

/**
 * Trait InsertOrdersTestData
 */
trait InsertOrdersTestData
{

    /**
     * @var SummitTicketType
     */
    static $default_ticket_type;

    /**
     * @var SummitBadgeType
     */
    static $default_badge_type;

    /**
     * @var SummitOrder[]
     */
    static $summit_orders = [];

    /**
     * @throws Exception
     */
    protected static function InsertOrdersTestData()
    {
        DB::setDefaultConnection("model");

        self::$default_badge_type = new SummitBadgeType();
        self::$default_badge_type->setName("BADGE TYPE1");
        self::$default_badge_type->setIsDefault(true);
        self::$default_badge_type->setDescription("BADGE TYPE1 DESCRIPTION");
        self::$summit->addBadgeType(self::$default_badge_type);

        self::$default_ticket_type = new SummitTicketType();
        self::$default_ticket_type->setCost(100);
        self::$default_ticket_type->setCurrency("USD");
        self::$default_ticket_type->setName("TICKET TYPE 1");
        self::$default_ticket_type->setQuantity2Sell(100);
        self::$default_ticket_type->setBadgeType(self::$default_badge_type);
        self::$summit->addTicketType(self::$default_ticket_type);

        for($i = 1 ; $i <= 10; $i++) {
            $order = new SummitOrder();
            $order->setBillingAddress1(sprintf( "ADDRESS %s", $i));
            $order->setBillingAddress2(sprintf("ADDRESS 2 %s", $i));
            $order->setNumber(sprintf("ORDER NBR %s", $i));
            $order->setOwnerCompany(sprintf("COMPANY %s", $i));
            $order->setOwnerFirstName(sprintf("FNAME %s", $i));
            $order->setOwnerSurname(sprintf("LNAME %s", $i));
            $order->setOwnerEmail(sprintf("test+%s@test.com", $i));
            $order->setPaymentMethodOffline();
            self::$summit->addOrder($order);
            $order->generateNumber();
            $ticket = new SummitAttendeeTicket();
            $ticket->setOrder($order);
            $ticket->setTicketType(self::$default_ticket_type);
            $ticket->generateNumber();
            self::$default_ticket_type->sell(1);
            $ticket->generateHash();
            $ticket->generateQRCode();
            $order->generateHash();
            $order->generateQRCode();
            $order->setPaid();
            $order->addTicket($ticket);
            self::$summit_orders[] = $order;
        }

        self::$em->persist(self::$summit);
        self::$em->flush();
    }
}
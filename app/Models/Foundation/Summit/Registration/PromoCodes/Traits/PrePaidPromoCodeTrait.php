<?php namespace models\summit;
/*
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

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

const LOCK_SKEW_TIME = 5;
/**
 * Trait PrePaidPromoCodeTrait
 * @package models\summit
 */
trait PrePaidPromoCodeTrait
{
    public function hasPrePaidTicketsAssignedBy(string $email):bool{
        $query = <<<DQL
SELECT COUNT(e.id)  
FROM models\summit\SummitAttendeeTicket e
JOIN e.owner o
JOIN e.order ord
LEFT JOIN o.member m
WHERE e.promo_code = :promo_code
AND ( o.email = :email OR m.email = :email)
AND ( ord.status = :status) AND (ord.payment_method = :payment_method)
DQL;

        $dql_query = $this->getEM()->createQuery($query)->setCacheable(false);

        $dql_query->setParameter("promo_code", $this);
        $dql_query->setParameter("email", trim($email));
        $dql_query->setParameter("status", IOrderConstants::PaidStatus);
        $dql_query->setParameter("payment_method", IOrderConstants::OfflinePaymentMethod);

        $res = $dql_query->getSingleScalarResult();
        return $res > 0;
    }
    /**
     * @param SummitTicketType $ticket_type
     * @return SummitAttendeeTicket|null
     */
    public function getNextAvailableTicketPerType(SummitTicketType $ticket_type): ?SummitAttendeeTicket
    {
        Log::debug
        (
            sprintf
            (
                "PrePaidPromoCodeTrait::getNextAvailableTicketPerType - ticket_type id: %s promo_code: %s",
                $ticket_type->getId(),
                $this->getCode()
            )
        );

        $excluded_ids = [];

        do {

            Log::debug
            (
                sprintf
                (
                    "PrePaidPromoCodeTrait::getNextAvailableTicketPerType - ticket_type id: %s promo_code : %s running with exclude ids %s",
                    $ticket_type->getId(),
                    $this->getCode(),
                    json_encode($excluded_ids)
                )
            );

            $query = <<<DQL
SELECT e  
FROM models\summit\SummitAttendeeTicket e
WHERE e.ticket_type = :type
AND e.promo_code = :promo_code
AND e.owner IS NULL
AND e.id NOT IN (:excluded_ids)
DQL;

            $dql_query = $this->getEM()->createQuery($query)->setCacheable(false);
            $dql_query->setParameter("type", $ticket_type);
            $dql_query->setParameter("promo_code", $this);
            $dql_query->setParameter("excluded_ids", implode(',', $excluded_ids));
            $result =  $dql_query->getResult();

            if (count($result) === 0) {
                Log::debug
                (
                    sprintf
                    (
                        "PrePaidPromoCodeTrait::getNextAvailableTicketPerType - ticket_type id: %s promo_code: %s exclude ids %s returned empty set.",
                        $ticket_type->getId(),
                        $this->getCode(),
                        json_encode($excluded_ids)
                    )
                );
                break;
            }

            $ticket = $result[0];

            Log::debug
            (
                sprintf
                (
                    "PrePaidPromoCodeTrait::getNextAvailableTicketPerType - ticket_type id: %s promo_code : %s got available ticket %s",
                    $ticket_type->getId(),
                    $this->getCode(),
                    $ticket->getId()
                )
            );

            $key = sprintf("prepaid.ticket.%s.lock", $ticket->getId());
            if (Cache::has($key)) {
                $excluded_ids[] = $ticket->getId();
                Log::debug
                (
                    sprintf
                    (
                        "PrePaidPromoCodeTrait::getNextAvailableTicketPerType - ticket_type id: %s  promo_code: %s ticket %s is already locked",
                        $ticket_type->getId(),
                        $this->getCode(),
                        $ticket->getId()
                    )
                );
                continue;
            }
            // lock
            Cache::put($key, $key, now()->addMinutes(LOCK_SKEW_TIME));
            Log::debug
            (
                sprintf
                (
                    "PrePaidPromoCodeTrait::getNextAvailableTicketPerType - ticket_type id: %s  promo_code: %s available ticket %s locked.",
                    $ticket_type->getId(),
                    $this->getCode(),
                    $ticket->getId()
                )
            );
            return $ticket;
        } while(1);

        return null;
    }

    /**
     * @return int
     */
    public function getQuantityUsed(): int
    {
        $query = <<<DQL
SELECT COUNT(e.id)  
FROM models\summit\SummitAttendeeTicket e
JOIN e.order ord
WHERE e.promo_code = :promo_code
AND e.owner IS NOT NULL
AND ( ord.status = :status) 
AND (ord.payment_method = :payment_method)
DQL;

        $dql_query = $this->getEM()->createQuery($query)->setCacheable(false);

        $dql_query->setParameter("promo_code", $this);
        $dql_query->setParameter("status", IOrderConstants::PaidStatus);
        $dql_query->setParameter("payment_method", IOrderConstants::OfflinePaymentMethod);

        return $dql_query->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function getQuantityAvailable(): int
    {
        $query = <<<DQL
SELECT COUNT(e.id)  
FROM models\summit\SummitAttendeeTicket e
JOIN e.order ord
WHERE e.promo_code = :promo_code
AND e.owner IS NULL
AND ( ord.status = :status) 
AND (ord.payment_method = :payment_method)
DQL;

        $dql_query = $this->getEM()->createQuery($query)->setCacheable(false);

        $dql_query->setParameter("promo_code", $this);
        $dql_query->setParameter("status", IOrderConstants::PaidStatus);
        $dql_query->setParameter("payment_method", IOrderConstants::OfflinePaymentMethod);

        return $dql_query->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function getQuantityRemaining(): int
    {
        $quantityAvailable = $this->getQuantityAvailable();
        $quantityUsed = $this->getQuantityUsed();

        Log::debug
        (
            sprintf
            (
                "PrePaidPromoCodeTrait::getQuantityRemaining promo_code %s quantityAvailable %s quantityUsed %s",
                $this->getCode(),
                $quantityAvailable,
                $quantityUsed
            )
        );

        return $quantityAvailable - $quantityUsed;
    }
}
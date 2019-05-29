<?php namespace App\Repositories\Summit;
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
use App\Models\Foundation\Summit\Repositories\ISummitRoomReservationRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\SummitRoomReservation;
/**
 * Class DoctrineSummitRoomReservationRepository
 * @package App\Repositories\Summit
 */
class DoctrineSummitRoomReservationRepository
    extends SilverStripeDoctrineRepository
    implements ISummitRoomReservationRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return SummitRoomReservation::class;
    }

    /**
     * @param string $payment_gateway_cart_id
     * @return SummitRoomReservation
     */
    public function getByPaymentGatewayCartId(string $payment_gateway_cart_id): SummitRoomReservation
    {
        return $this->findOneBy(["payment_gateway_cart_id" => trim($payment_gateway_cart_id)]);
    }
}
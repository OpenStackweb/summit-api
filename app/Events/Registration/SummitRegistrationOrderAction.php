<?php namespace App\Events;
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
use Illuminate\Queue\SerializesModels;
/**
 * Class SummitRegistrationOrderAction
 * @package App\Events
 */
abstract class SummitRegistrationOrderAction
{

    use SerializesModels;
    /**
     * @var int
     */
    private $order_id;

    /**
     * BookableRoomReservationAction constructor.
     * @param int $order_id
     */
    public function __construct(int $order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->order_id;
    }
}
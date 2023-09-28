<?php namespace App\ModelSerializers\Locations;
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

use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitRoomReservation;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SummitRoomReservationSerializer
 * @package App\ModelSerializers\Locations
 */
final class SummitRoomReservationSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'RoomId'                    => 'room_id:json_int',
        'OwnerId'                   => 'owner_id:json_int',
        'Amount'                    => 'amount:json_int',
        'RefundedAmount'            => 'refunded_amount:json_int',
        'Currency'                  => 'currency:json_string',
        'Status'                    => 'status:json_string',
        'StartDatetime'             => 'start_datetime:datetime_epoch',
        'EndDatetime'               => 'end_datetime:datetime_epoch',
        'ApprovedPaymentDate'       => 'approved_payment_date:datetime_epoch',
        'LastError'                 => 'last_error:json_string',
        'PaymentGatewayClientToken' => 'payment_gateway_client_token:json_string',
        'PaymentMethod'             => 'payment_method:json_string',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $reservation = $this->object;
        if(!$reservation instanceof  SummitRoomReservation)
            return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'room': {
                        unset($values['room_id']);
                        $values['room'] = SerializerRegistry::getInstance()->getSerializer
                        (
                            $reservation->getRoom(),
                            $this->getSerializerType($relation)
                        )->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                    }
                    break;
                    case 'owner': {
                        unset($values['owner_id']);
                        $values['owner'] = SerializerRegistry::getInstance()->getSerializer
                        (
                            $reservation->getOwner(),
                            $this->getSerializerType($relation)
                        )->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                    }
                    break;
                }
            }
        }

        return $values;
    }

    /**
     * @param string|null $relation
     * @return string
     */
    protected function getSerializerType(?string $relation = null): string
    {
        $serializer_type = SerializerRegistry::SerializerType_Public;
        $current_member  = $this->resource_server_context->getCurrentUser();
        if(!is_null($current_member)){
            if($current_member->isAdmin()){
                $serializer_type = SerializerRegistry::SerializerType_Private;
            }
        }
        return $serializer_type;
    }
}
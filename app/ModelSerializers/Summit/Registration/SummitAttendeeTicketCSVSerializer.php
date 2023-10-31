<?php namespace App\ModelSerializers\Summit\Registration;
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
use models\summit\SummitAttendeeTicket;
use models\summit\SummitBadgeFeatureType;
use models\summit\SummitOrderExtraQuestionType;
use ModelSerializers\SilverStripeSerializer;
use Illuminate\Support\Facades\Log;
/**
 * Class SummitAttendeeTicketCSVSerializer
 * @package App\ModelSerializers\Summit\Registration
 */
final class SummitAttendeeTicketCSVSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Number' => 'number:json_string',
        'Status' => 'status:json_string',
        'OwnerId' => 'attendee_id:json_int',
        'OwnerFirstName' => 'attendee_first_name:json_string',
        'OwnerSurname' => 'attendee_last_name:json_string',
        'OwnerEmail' => 'attendee_email:json_string',
        'OwnerCompany' => 'attendee_company:json_string',
        'ExternalAttendeeId' => 'external_attendee_id:json_string',
        'BoughtDate' => 'purchase_date:datetime_epoch',
        'TicketTypeId' => 'ticket_type_id:json_int',
        'TicketTypeName' => 'ticket_type_name:json_string',
        'OrderId' => 'order_id:json_int',
        'BadgeId' => 'badge_id:json_int',
        'PromoCodeId' => 'promo_code_id:json_int',
        'PromoCodeValue' => 'promo_code:json_string',
        'BadgeTypeId' => 'badge_type_id:json_int',
        'BadgeTypeName' => 'badge_type_name:json_string',
        'Active'    => 'is_active:json_bool',
        'Currency' => 'currency:json_string',
        'TicketTypeCost' => 'ticket_type_cost:json_float',
        'RawCost' => 'raw_cost:json_float',
        'Discount' => 'discounted_amount:json_float',
        'FinalAmount' => 'amount_paid:json_float',
        'RefundedAmount' => 'refunded_amount:json_float',
        'FinalAmountAdjusted' => 'amount_paid_adjusted:json_float',
        'BadgePrintsCount' => 'badge_prints_count:json_int',
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
        $ticket = $this->object;
        if (!$ticket instanceof SummitAttendeeTicket) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        if (isset($params['features_types'])) {
            $ticket_features = $ticket->getBadgeFeaturesNames();
            foreach ($params['features_types'] as $features_type) {
                if (!$features_type instanceof SummitBadgeFeatureType) continue;
                $values[$features_type->getName()] = in_array($features_type->getName(), $ticket_features) ? '1' : '0';
            }
        }

        $ticket_owner = $ticket->getOwner();

        if (isset($params['ticket_questions'])) {
            foreach ($params['ticket_questions'] as $question) {
                if (!$question instanceof SummitOrderExtraQuestionType) continue;
                $values[$question->getLabel()] = '';
                if (!is_null($ticket_owner)) {
                    $value = $ticket_owner->getExtraQuestionAnswerValueByQuestion($question);
                    Log::debug(sprintf("SummitAttendeeTicketCSVSerializer::serialize question %s value %s", $question->getId(), $value));
                    if(is_null($value)) continue;
                    $values[$question->getLabel()] = $question->getNiceValue($value);
                }
            }
        }

        // extra fields

        if($ticket->hasOwner()) {
            $values['attendee_checked_in'] = $ticket->getOwner()->hasCheckedIn();
        }

        if($ticket->hasPromoCode()) {
            $tags = [];
            foreach ($ticket->getPromoCode()->getTags() as $tag){
                $tags[] = $tag->getTag();
            }
            $values['promo_code_tags'] = implode('|', $tags );
        }

        return $values;
    }
}
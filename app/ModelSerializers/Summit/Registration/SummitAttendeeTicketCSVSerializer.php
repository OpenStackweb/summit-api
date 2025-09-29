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
        'BadgePrintsCount' => 'badge_prints_count:json_int',
        'Currency' => 'currency:json_string',
         // cost fields
        'TicketTypeCost' => 'current_ticket_price:json_float',
        'RawCost' => 'ticket_price:json_float',
        'Discount' => 'discount:json_float',
        'RefundedTaxesAmount' => 'refunded_tax_fee:json_float',
        'NetSellingPrice' => 'net_price:json_float',
        'TotalRefundedAmount' => 'total_refunded:json_float',
        'FinalAmountAdjusted' => 'total_paid:json_float',
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
                $question_label = html_entity_decode(strip_tags($question->getLabel()));
                $values[$question_label] = '';
                if (!is_null($ticket_owner)) {
                    $value = $ticket_owner->getExtraQuestionAnswerValueByQuestion($question);
                    if(is_null($value)) continue;
                    $values[$question_label] = $question->getNiceValue($value);
                }
            }
        }

        // extra fields
        $values['attendee_checked_in'] = '0';
        if($ticket->hasOwner()) {
            $values['attendee_checked_in'] = $ticket->getOwner()->hasCheckedIn();
        }

        $values['promo_code_tags'] = '';
        if($ticket->hasPromoCode()) {
            $tags = [];
            foreach ($ticket->getPromoCode()->getTags() as $tag){
                $tags[] = $tag->getTag();
            }
            $values['promo_code_tags'] = implode('|', $tags );
        }

        $notes = [];
        foreach ($ticket->getOrderedNotes() as $note){
            $notes[] = $note->getContent();
        }
        $values['notes'] = implode("|", $notes);


        // taxes

        foreach($ticket->getAppliedTaxes() as $appliedTax){
            $values[sprintf("%s_rate", strtolower($appliedTax->getTax()->getName()))] = $appliedTax->getRate();
            $values[sprintf("%s_price", strtolower($appliedTax->getTax()->getName()))] = $appliedTax->getAmount();
        }

        // tags
        $attendee_tags = [];
        if($ticket->hasOwner()) {
            foreach ($ticket->getOwner()->getTags() as $tag) {
                $attendee_tags[] = $tag->getTag();
            }
        }
        $values['attendee_tags'] = implode("|", $attendee_tags);

        return $values;
    }
}
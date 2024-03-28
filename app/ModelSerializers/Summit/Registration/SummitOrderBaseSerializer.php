<?php namespace ModelSerializers;
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

use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitOrder;

/**
 * Class SummitOrderBaseSerializer
 * @package App\ModelSerializers
 */
class SummitOrderBaseSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Number' => 'number:json_string',
        'Status' => 'status:json_string',
        'PaymentMethod' => 'payment_method:json_string',
        'OwnerFirstName' => 'owner_first_name:json_string',
        'OwnerSurname' => 'owner_last_name:json_string',
        'OwnerEmail' => 'owner_email:json_string',
        'OwnerCompanyName' => 'owner_company:json_string',
        'CompanyId' => 'owner_company_id:json_int',
        'OwnerId' => 'owner_id:json_string',
        'SummitId' => 'summit_id:json_int',
        'Currency' => 'currency:json_string',
        'CurrencySymbol' => 'currency_symbol:json_string',
    ];

    protected static $allowed_relations = [
        'extra_questions',
        'tickets',
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
        $order = $this->object;
        if (!$order instanceof SummitOrder) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('tickets', $relations)) {
            $tickets = [];

            foreach ($order->getTickets() as $ticket) {
                $tickets[] = $ticket->getId();
            }
            $values['tickets'] = $tickets;
        }

        if (in_array('extra_questions', $relations)) {
            $extra_question_answers = [];

            foreach ($order->getExtraQuestionAnswers() as $answer) {
                $extra_question_answers[] = $answer->getId();
            }
            $values['extra_questions'] = $extra_question_answers;
        }

        if (!empty($expand)) {
            Log::debug(sprintf("SummitOrderBaseSerializer::serialize expand %s", $expand));

            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'tickets':
                        {
                            if (!in_array('tickets', $relations)) break;
                            $tickets = [];
                            unset($values['tickets']);
                            foreach ($order->getTickets() as $ticket) {
                                $tickets[] = SerializerRegistry::getInstance()->getSerializer($ticket)->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                            $values['tickets'] = $tickets;
                        }
                        break;
                    case 'owner':
                        {

                            if ($order->hasOwner()) {
                                unset($values['owner_id']);
                                $values['owner'] = SerializerRegistry::getInstance()->getSerializer($order->getOwner())->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                        }
                        break;
                    /*
                    case 'summit':{
                        unset($values['summit_id']);
                        $values['summit'] = SerializerRegistry::getInstance()->getSerializer($order->getSummit())->serialize(null,
                            [
                                'id',
                                'start_date',
                                'end_date',
                                'registration_begin_date',
                                'registration_end_date',
                                'reassign_ticket_till_date'
                            ], [], []);
                    }
                    break;
                    */
                    case 'owner_company':
                        {

                            if ($order->hasOwnerCompany()) {
                                unset($values['owner_company_id']);
                                $values['owner_company'] = SerializerRegistry::getInstance()->getSerializer($order->getCompany())->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                        }
                        break;
                    case 'extra_questions':
                        {
                            if (!in_array('extra_questions', $relations)) break;
                            $extra_question_answers = [];
                            unset($values['extra_questions']);
                            foreach ($order->getExtraQuestionAnswers() as $answer) {
                                $extra_question_answers[] = SerializerRegistry::getInstance()->getSerializer($answer)->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                            $values['extra_questions'] = $extra_question_answers;
                        }
                        break;
                }
            }
        }


        return $values;
    }
}
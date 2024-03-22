<?php namespace App\ModelSerializers\Summit\Registration;
/*
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
use App\ModelSerializers\Summit\SponsorUserInfoGrantSerializer;
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SponsorUserInfoGrant;
use models\summit\SummitOrderExtraQuestionType;
/**
 * Class SponsorUserInfoGrantCSVSerializer
 * @package App\ModelSerializers\Summit\Registration
 */
class SponsorUserInfoGrantCSVSerializer extends SponsorUserInfoGrantSerializer
{
    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $grant = $this->object;
        if (!$grant instanceof SponsorUserInfoGrant) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        $values['notes'] = 'VIRTUAL';

        if (isset($params['ticket_questions'])) {

            $ticket_owner = null;
            $member = $grant->getAllowedUser();
            $sponsor = $grant->getSponsor();
            $summit = $sponsor->getSummit();
            $tickets = $member->getPaidSummitTickets($summit);
            if(count($tickets)) {
                $ticket = $tickets[0];
                $ticket_owner = $ticket->getOwner();
            }
            foreach ($params['ticket_questions'] as $question) {
                if (!$question instanceof SummitOrderExtraQuestionType) continue;
                $label = AbstractSerializer::getCSVLabel($question->getLabel());
                $values[$label] = '';
                if (!is_null($ticket_owner)) {
                    $value = $ticket_owner->getExtraQuestionAnswerValueByQuestion($question);
                    if(is_null($value)) continue;
                    $values[$label] = $question->getNiceValue($value);
                }
            }
        }
        return $values;
    }
}
<?php namespace App\ModelSerializers\Summit;
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

use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SponsorBadgeScan;
use models\summit\SummitOrderExtraQuestionType;
/**
 * Class SponsorBadgeScanCSVSerializer
 * @package App\ModelSerializers\Summit
 */
final class SponsorBadgeScanCSVSerializer extends AbstractSerializer
{
    protected static $array_mappings = [
        'Id'           => 'id:json_int',
        'ScanDate'     => 'scan_date:datetime_epoch',
        'QRCode'       => 'qr_code:json_string',
        'Notes'        => 'notes:json_string',
        'SponsorId'    => 'sponsor_id:json_int',
        'UserId'       => 'scanned_by_id:json_int',
        'BadgeId'      => 'badge_id:json_int',
        'AttendeeFirstName' => 'attendee_first_name:json_string',
        'AttendeeLastName' => 'attendee_last_name:json_string',
        'AttendeeEmail' => 'attendee_email:json_string',
        'AttendeeCompany' => 'attendee_company:json_string',
    ];


    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        $scan = $this->object;
        if (!$scan instanceof SponsorBadgeScan) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (isset($params['ticket_questions'])) {

            $ticket_owner = null;
            $badge = $scan->getBadge();

            if(!is_null($badge)) {
                $ticket_owner = $badge->getTicket()->getOwner();
            }

            foreach ($params['ticket_questions'] as $question) {
                $label = $question->getCSVLabel();

                if (!$question instanceof SummitOrderExtraQuestionType) continue;
                $values[$label] = '';

                if (!is_null($ticket_owner)) {
                    $value = $ticket_owner->getExtraQuestionAnswerValueByQuestion($question);
                    if(is_null($value)) continue;
                    $values[$label] = $question->getNiceValue($value);
                }
            }
        }

        if (isset($params['sponsor_questions'])) {

            foreach ($params['sponsor_questions'] as $question) {
                if (!$question instanceof SummitSponsorExtraQuestionType) continue;

                $label = $question->getCSVLabel();
                $values[$label] = '';

                $value = $scan->getExtraQuestionAnswerValueByQuestion($question);
                if(is_null($value)) continue;
                $values[$label] = $question->getNiceValue($value);
            }
        }

        return $values;
    }
}
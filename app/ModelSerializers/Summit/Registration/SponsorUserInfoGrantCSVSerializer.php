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

use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use App\ModelSerializers\Summit\SponsorUserInfoGrantSerializer;
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SponsorUserInfoGrant;
use models\summit\SummitLeadReportSetting;
use models\summit\SummitOrderExtraQuestionType;
/**
 * Class SponsorUserInfoGrantCSVSerializer
 * @package App\ModelSerializers\Summit\Registration
 */
class SponsorUserInfoGrantCSVSerializer extends SponsorUserInfoGrantSerializer
{

    private static function isAllowedExtraQuestion(array $allowed_extra_questions, int $extra_question_id): bool
    {
        if (count($allowed_extra_questions) > 0 && $allowed_extra_questions[0] == '*') return true;

        foreach($allowed_extra_questions as $allowed_extra_question) {
            if ($allowed_extra_question['id'] == $extra_question_id) return true;
        }
        return false;
    }

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

        $sponsor = $grant->getSponsor();
        $sponsor_questions = $sponsor->getExtraQuestions();
        $setting = $sponsor->getSummit()->getLeadReportSettingFor($sponsor);
        $setting_columns = $setting->getColumns();


        // remove not allowed string columns and sort them by setting columns order
        $new_values = [];
        foreach(array_values($setting_columns) as $column) {
            if(!is_string($column)) continue;
            if(!isset($values[$column])) continue;
            $new_values[$column] = $values[$column];
        }
        $values = $new_values;

        $sponsor_questions_values  = [];
        if (isset($setting_columns[SummitLeadReportSetting::SponsorExtraQuestionsKey])
            && count($setting_columns[SummitLeadReportSetting::SponsorExtraQuestionsKey]) > 0) {

            $allowed_sponsor_extra_questions = $setting_columns[SummitLeadReportSetting::SponsorExtraQuestionsKey];

            foreach ($sponsor_questions as $question) {
                if (!$question instanceof SummitSponsorExtraQuestionType) continue;

                if (!self::isAllowedExtraQuestion($allowed_sponsor_extra_questions, $question->getId())) continue;

                $label = AbstractSerializer::getCSVLabel($question->getLabel());
                Log::debug
                (
                    sprintf
                    (
                        "SponsorBadgeScanCSVSerializer::serialize question %s label %s order %s",
                        $question->getId(),
                        $label,
                        $question->getOrder()
                    )
                );

                $sponsor_questions_values[$label] = '';

                $value = $grant->getExtraQuestionAnswerValueByQuestion($question);
                if (is_null($value)) continue;
                $sponsor_questions_values[$label] = $question->getNiceValue($value);
            }
        }

        $ticket_questions_values = [];
        if (isset($params['ticket_questions'])
            && isset($setting_columns[SummitLeadReportSetting::AttendeeExtraQuestionsKey])
            && count($setting_columns[SummitLeadReportSetting::AttendeeExtraQuestionsKey]) > 0) {

            $ticket_owner = null;
            $badge = $grant->getBadge();

            $allowed_attendee_extra_questions = $setting_columns[SummitLeadReportSetting::AttendeeExtraQuestionsKey];

            if (!is_null($badge)) {
                $ticket_owner = $badge->getTicket()->getOwner();
            }

            foreach ($params['ticket_questions'] as $question) {
                if (!self::isAllowedExtraQuestion($allowed_attendee_extra_questions, $question->getId())) continue;

                $label = AbstractSerializer::getCSVLabel($question->getLabel());

                if (!$question instanceof SummitOrderExtraQuestionType) continue;
                $ticket_questions_values[$label] = '';

                if (!is_null($ticket_owner)) {
                    $value = $ticket_owner->getExtraQuestionAnswerValueByQuestion($question);
                    if (is_null($value)) continue;
                    $ticket_questions_values[$label] = $question->getNiceValue($value);
                }
            }
        }

        $values = array_merge($values, $sponsor_questions_values, $ticket_questions_values);

        return $values;
    }
}
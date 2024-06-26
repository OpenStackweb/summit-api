<?php namespace ModelSerializers;
/**
 * Copyright 2018 OpenStack Foundation
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

use App\Models\Foundation\Summit\SelectionPlan;
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;

/**
 * Class SelectionPlanSerializer
 * @package App\ModelSerializers\Summit
 */
final class SelectionPlanSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name' => 'name:json_string',
        'Enabled' => 'is_enabled:json_boolean',
        'Hidden' => 'is_hidden:json_boolean',
        'SubmissionBeginDate' => 'submission_begin_date:datetime_epoch',
        'SubmissionEndDate' => 'submission_end_date:datetime_epoch',
        'SubmissionLockDownPresentationStatusDate' => 'submission_lock_down_presentation_status_date:datetime_epoch',
        'MaxSubmissionAllowedPerUser' => 'max_submission_allowed_per_user:json_int',
        'VotingBeginDate' => 'voting_begin_date:datetime_epoch',
        'VotingEndDate' => 'voting_end_date:datetime_epoch',
        'SelectionBeginDate' => 'selection_begin_date:datetime_epoch',
        'SelectionEndDate' => 'selection_end_date:datetime_epoch',
        'SummitId' => 'summit_id:json_int',
        'AllowNewPresentations' => 'allow_new_presentations:json_boolean',
        'SubmissionPeriodDisclaimer' => 'submission_period_disclaimer:json_string',
        'PresentationCreatorNotificationEmailTemplate' => 'presentation_creator_notification_email_template:json_string',
        'PresentationModeratorNotificationEmailTemplate' => 'presentation_moderator_notification_email_template:json_string',
        'PresentationSpeakerNotificationEmailTemplate' => 'presentation_speaker_notification_email_template:json_string',
        'Type' => 'type:json_string',
        'AllowProposedSchedules' => 'allow_proposed_schedules:json_boolean',
        'AllowTrackChangeRequests' => 'allow_track_change_requests:json_boolean',
    ];

    protected static $allowed_relations = [
        'track_groups',
        'extra_questions',
        'event_types',
        'track_chair_rating_types',
        'allowed_presentation_action_types',
        'allowed_presentation_questions',
        'allowed_presentation_editable_questions',
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
        $selection_plan = $this->object;
        if (!$selection_plan instanceof SelectionPlan) return [];
        Log::debug(sprintf("SelectionPlanSerializer expand %s", $expand));

        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('track_groups', $relations) && !isset($values['track_groups'])) {
            $category_groups = [];
            foreach ($selection_plan->getCategoryGroups() as $group) {
                $category_groups[] = $group->getId();
            }
            $values['track_groups'] = $category_groups;
        }

        if (in_array('extra_questions', $relations)) {
            if (!isset($values['extra_questions'])) {
                $extra_questions = [];
                foreach ($selection_plan->getExtraQuestions() as $extraQuestion) {
                    $extra_questions[] = $extraQuestion->getQuestionType()->getId();
                }
                $values['extra_questions'] = $extra_questions;
            }
        }

        if (in_array('event_types', $relations) && !isset($values['event_types'])) {
            $event_types = [];
            foreach ($selection_plan->getEventTypes() as $eventType) {
                $event_types[] = $eventType->getId();
            }
            $values['event_types'] = $event_types;
        }

        if (in_array('track_chair_rating_types', $relations) && !isset($values['track_chair_rating_types'])) {
            $track_chair_rating_types = [];
            foreach ($selection_plan->getTrackChairRatingTypes() as $ratingType) {
                $track_chair_rating_types[] = $ratingType->getId();
            }
            $values['track_chair_rating_types'] = $track_chair_rating_types;
        }

        if (in_array('allowed_presentation_action_types', $relations) && !isset($values['allowed_presentation_action_types'])) {
            $allowed_presentation_action_types = [];
            foreach ($selection_plan->getPresentationActionTypes() as $actionType) {
                $allowed_presentation_action_types[] = $actionType->getId();
            }
            $values['allowed_presentation_action_types'] = $allowed_presentation_action_types;
        }

        if(in_array('allowed_presentation_questions', $relations)){
            $allowed_presentation_questions = [];
            foreach ($selection_plan->getAllowedPresentationQuestions() as $question){
                $allowed_presentation_questions[] = $question->getType();
            }
            $values['allowed_presentation_questions'] = $allowed_presentation_questions;
        }

        if(in_array('allowed_presentation_editable_questions', $relations)){
            $allowed_presentation_editable_questions = [];
            foreach ($selection_plan->getAllowedEditablePresentationQuestions() as $question){
                $allowed_presentation_editable_questions[] = $question->getType();
            }
            $values['allowed_presentation_editable_questions'] = $allowed_presentation_editable_questions;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'track_groups' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getCategoryGroups',
        ],
        'extra_questions' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getExtraQuestions',
        ],
        'event_types' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getEventTypes',
        ],
        'track_chair_rating_types' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getTrackChairRatingTypes',
        ],
        'allowed_presentation_action_types' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getPresentationActionTypes',
        ],
        'summit' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'summit_id',
            'getter' => 'getSummit',
            'has' => 'hasSummit'
        ],
    ];
}
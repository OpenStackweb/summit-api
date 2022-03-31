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
use Libs\ModelSerializers\AbstractSerializer;
/**
 * Class SelectionPlanSerializer
 * @package App\ModelSerializers\Summit
 */
final class SelectionPlanSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'                        => 'name:json_string',
        'Enabled'                     => 'is_enabled:json_boolean',
        'SubmissionBeginDate'         => 'submission_begin_date:datetime_epoch',
        'SubmissionEndDate'           => 'submission_end_date:datetime_epoch',
        'MaxSubmissionAllowedPerUser' => 'max_submission_allowed_per_user:json_int',
        'VotingBeginDate'             => 'voting_begin_date:datetime_epoch',
        'VotingEndDate'               => 'voting_end_date:datetime_epoch',
        'SelectionBeginDate'          => 'selection_begin_date:datetime_epoch',
        'SelectionEndDate'            => 'selection_end_date:datetime_epoch',
        'SummitId'                    => 'summit_id:json_int',
        'AllowNewPresentations'       => 'allow_new_presentations:json_boolean',
        'SubmissionPeriodDisclaimer'  => 'submission_period_disclaimer:json_string'
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
        $values = parent::serialize($expand, $fields, $relations, $params);

        $category_groups  = [];
        foreach ($selection_plan->getCategoryGroups() as $group) {
            $category_groups[] = $group->getId();
        }

        $values['track_groups'] = $category_groups;

        $extra_questions  = [];
        foreach ($selection_plan->getExtraQuestions() as $extraQuestion) {
            $extra_questions[] = $extraQuestion->getId();
        }

        $values['extra_questions'] = $extra_questions;

        if (!empty($expand)) {
            $relations = explode(',', $expand);
            foreach ($relations as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'track_groups':{
                        $category_groups  = [];
                        foreach ($selection_plan->getCategoryGroups() as $group) {
                            $category_groups[] = SerializerRegistry::getInstance()->getSerializer($group)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['track_groups'] = $category_groups;
                    }
                    break;
                    case 'extra_questions':{
                        $extra_questions  = [];
                        foreach ($selection_plan->getExtraQuestions() as $extraQuestion) {
                            $extra_questions[] = SerializerRegistry::getInstance()->getSerializer($extraQuestion)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['extra_questions'] = $extra_questions;
                    }
                        break;
                    case 'summit':{
                        unset($values['summit_id']);
                        $values['summit'] = SerializerRegistry::getInstance()->getSerializer($selection_plan->getSummit())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                    }
                    break;
                }
            }
        }

        return $values;
    }
}
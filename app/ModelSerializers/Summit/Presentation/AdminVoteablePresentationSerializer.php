<?php namespace ModelSerializers;
/**
 * Copyright 2022 OpenStack Foundation
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
use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\Presentation;
/**
 * Class AdminVoteablePresentationSerializer
 * @package ModelSerializers
 */
final class AdminVoteablePresentationSerializer extends AdminPresentationSerializer
{

    protected static $allowed_relations = [
        'voters',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize(
        $expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $presentation = $this->object;
        if (!$presentation instanceof Presentation) return [];

        if (!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        $beginVotingDate = $params['begin_attendee_voting_period_date'] ?? null;
        $endVotingDate = $params['end_attendee_voting_period_date'] ?? null;
        $values['votes_count'] = $presentation->getAttendeeVotesCount($beginVotingDate, $endVotingDate);

        if(in_array("voters", $relations)) {
            $voters = [];
            foreach ($presentation->getVoters($beginVotingDate, $endVotingDate) as $voter) {
                   $voters[] = $voter->getId();
            }
            $values['voters'] = $voters;
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'voters':
                    {
                        $voters = [];
                        foreach ($presentation->getVoters($beginVotingDate, $endVotingDate) as $voter) {
                            $voters[] = SerializerRegistry::getInstance()->getSerializer($voter)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                $params
                            );
                        }
                        $values['voters'] = $voters;
                    }
                    break;
                }
            }
        }


        return $values;
    }
}
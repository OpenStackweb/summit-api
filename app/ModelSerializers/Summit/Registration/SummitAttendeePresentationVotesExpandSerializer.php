<?php namespace ModelSerializers;
/**
 * Copyright 2026 OpenStack Foundation
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

/**
 * Class SummitAttendeePresentationVotesExpandSerializer
 * @package ModelSerializers
 */
class SummitAttendeePresentationVotesExpandSerializer extends Many2OneExpandSerializer
{
    /**
     * @param $entity
     * @param array $values
     * @param string $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @param bool $should_verify_relation
     * @return array
     */
    public function serialize
    (
        $entity,
        array $values,
        string $expand,
        array $fields = [],
        array $relations = [],
        array $params = [],
        bool $should_verify_relation = false
    ): array
    {
        $values = $this->unsetOriginalAttribute($values);
        if ($should_verify_relation && !in_array($this->attribute, $relations)) return $values;

        $childExpand    = AbstractSerializer::filterExpandByPrefix($expand, $this->attribute);
        $childFields    = AbstractSerializer::filterFieldsByPrefix($fields, $this->attribute);
        $childRelations = AbstractSerializer::filterFieldsByPrefix($relations, $this->attribute);
        $registry       = SerializerRegistry::getInstance();

        $beginVotingDate = $params['begin_attendee_voting_period_date'] ?? null;
        $endVotingDate   = $params['end_attendee_voting_period_date'] ?? null;
        $trackGroupId    = $params['presentation_votes_track_group_id'] ?? null;

        $res = [];
        foreach ($entity->getPresentationVotes($beginVotingDate, $endVotingDate, $trackGroupId) as $vote) {
            $res[] = $registry->getSerializer($vote)->serialize($childExpand, $childFields, $childRelations, $params);
        }
        $values[$this->attribute] = $res;

        return $values;
    }
}

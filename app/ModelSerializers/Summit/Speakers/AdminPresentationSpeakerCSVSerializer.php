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

use libs\utils\JsonUtils;
use models\summit\PresentationSpeaker;
/**
 * Class AdminPresentationSpeakerCSVSerializer
 * @package ModelSerializers
 */
final class AdminPresentationSpeakerCSVSerializer extends PresentationSpeakerBaseSerializer
{
    protected static $array_mappings = [
        'Notes' => 'notes:json_string',
    ];

    protected static $allowed_relations = [
        'accepted_presentations',
        'alternate_presentations',
        'rejected_presentations'
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = []) : array
    {
        if(!count($relations)) $relations = $this->getAllowedRelations();
        $speaker = $this->object;

        if(!$speaker instanceof PresentationSpeaker) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);
        $values['email'] = JsonUtils::toJsonString($speaker->getEmail());
        $summit = isset($params['summit'])? $params['summit']:null;

        if(isset($values['bio'])){
            $values['bio'] = strip_tags($values['bio']);
        }

        if (in_array('accepted_presentations', $relations) && !is_null($summit)) {
            $accepted_presentations = $speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleSpeaker, true, [] , $params['filter'] ?? null);
            $moderated_accepted_presentations = $speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleModerator, true, [] , $params['filter'] ?? null);
            $all_accepted_presentations = array_merge($accepted_presentations, $moderated_accepted_presentations);

            $values['accepted_presentations'] = join("|", array_map(function ($value): string {
                return "{$value->getId()}-{$value->getTitle()}";
            }, $all_accepted_presentations));
            $values['accepted_presentations_count'] = count($all_accepted_presentations);
        }

        if (in_array('alternate_presentations', $relations) && !is_null($summit)) {
            $alternate_presentations = $speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleSpeaker, false, [] , false, $params['filter'] ?? null);
            $moderated_alternate_presentations = $speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleModerator,false, [] , false, $params['filter'] ?? null);
            $all_alternate_presentations = array_merge($alternate_presentations, $moderated_alternate_presentations);

            $values['alternate_presentations'] = join("|", array_map(function ($value): string {
                return "{$value->getId()}-{$value->getTitle()}";
            }, $all_alternate_presentations));
            $values['alternate_presentations_count'] = count($all_alternate_presentations);
        }

        if (in_array('rejected_presentations', $relations) && !is_null($summit)) {
            $rejected_presentations = $speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleSpeaker, false, [] , $params['filter'] ?? null);
            $moderated_rejected_presentations = $speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleModerator,false, [] , $params['filter'] ?? null);
            $all_rejected_presentations = array_merge($rejected_presentations, $moderated_rejected_presentations);

            $values['rejected_presentations'] = join("|", array_map(function ($value): string {
                return "{$value->getId()}-{$value->getTitle()}";
            }, $all_rejected_presentations));
            $values['rejected_presentations_count'] = count($all_rejected_presentations);
        }
        return $values;
    }
}
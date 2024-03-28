<?php namespace ModelSerializers;
use models\summit\Presentation;

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

/**
 * Class PresentationEmailSerializer
 * @package ModelSerializers
 */
final class SpeakerPresentationEmailSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Title' => 'title:json_string',
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

        $presentation = $this->object;

        if (!$presentation instanceof Presentation) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        $track = $presentation->getCategory();
        if(!is_null($track)) {
            $values['track'] = [
                'id' => $track->getId(),
                'name' => $track->getTitle()
            ];
        }

        $selection_plan = $presentation->getSelectionPlan();
        if(!is_null($selection_plan)){
            $values['selection_plan'] =[
                'id' => $selection_plan->getId(),
                'name' => $selection_plan->getName(),
            ];
        }

        $speakers = [];
        foreach ($presentation->getSpeakers() as $speaker){
            $speakers[] = [
                'id' => $speaker->getId(),
                'full_name'=> $speaker->getFullName(),
                'email' => $speaker->getEmail()
            ];
        }

        $values['speakers'] = $speakers;
        $moderator = $presentation->getModerator();

        if(!is_null($moderator)) {
            $values['moderator'] = [
                'id' => $moderator->getId(),
                'full_name' => $moderator->getFullName(),
                'email' => $moderator->getEmail()
            ];
        }
        return $values;
    }
}
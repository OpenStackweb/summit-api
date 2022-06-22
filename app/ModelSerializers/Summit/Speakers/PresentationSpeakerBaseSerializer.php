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
use models\summit\PresentationSpeaker;

/**
 * Class PresentationSpeakerBaseSerializer
 * @package ModelSerializers
 */
abstract class PresentationSpeakerBaseSerializer extends SilverStripeSerializer
{
    protected static $allowed_relations = [
        'member',
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
        $speaker                          = $this->object;

        if(!$speaker instanceof PresentationSpeaker) return [];

        $values     = parent::serialize($expand, $fields, $relations, $params);
        $summit     = isset($params['summit'])? $params['summit']:null;
        $published  = isset($params['published'])? intval($params['published']):true;

        if(!is_null($summit)) {
            $featured = $summit->getFeatureSpeaker($speaker);
            $values['featured']                = !is_null($featured);
            $values['order']                   = is_null($featured) ? 0 : $featured->getOrder();
            $values['presentations']           = $speaker->getPresentationIds($summit->getId(), $published);
            $values['moderated_presentations'] = $speaker->getModeratedPresentationIds($summit->getId(), $published);
        }

        if (in_array('member', $relations) && $speaker->hasMember())
        {
            $member                         = $speaker->getMember();
            $values['gender']               = $member->getGender();
            $values['member_id']            = intval($member->getId());
            $values['member_external_id']   = intval($member->getUserExternalId());
        }

        if(empty($values['first_name']) || empty($values['last_name'])){

            $first_name = '';
            $last_name  = '';
            if ($speaker->hasMember())
            {
                $member     = $speaker->getMember();
                $first_name = $member->getFirstName();
                $last_name  = $member->getLastName();
            }
            $values['first_name'] = $first_name;
            $values['last_name']  = $last_name;
        }

        return $values;
    }
}
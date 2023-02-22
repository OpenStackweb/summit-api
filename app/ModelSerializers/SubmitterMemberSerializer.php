<?php namespace ModelSerializers;

/**
 * Copyright 2023 OpenStack Foundation
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
use models\main\Member;
use models\summit\PresentationSpeaker;

/**
 * Class SubmitterMemberSerializer
 * @package ModelSerializers
 */
final class SubmitterMemberSerializer extends AdminMemberSerializer
{
    protected static $allowed_relations = [
        'accepted_presentations',
        'alternate_presentations',
        'rejected_presentations',
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
        $submitter = $this->object;

        if(!$submitter instanceof Member) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        if (!$submitter->hasSpeaker()) return $values;

        $speaker = $submitter->getSpeaker();
        $summit  = isset($params['summit'])? $params['summit']:null;

        if (in_array('accepted_presentations', $relations) && !is_null($summit)) {
            $accepted_presentation_ids = $speaker->getAcceptedPresentationIds($summit, PresentationSpeaker::RoleSpeaker, false, [], $params['filter'] ?? null);
            $moderated_accepted_presentation_ids = $speaker->getAcceptedPresentationIds($summit, PresentationSpeaker::RoleModerator, false, [], $params['filter'] ?? null);
            $values['accepted_presentations'] = array_merge($accepted_presentation_ids, $moderated_accepted_presentation_ids);
        }

        if (in_array('alternate_presentations', $relations) && !is_null($summit)) {
            $alternate_presentation_ids = $speaker->getAlternatePresentationIds($summit, PresentationSpeaker::RoleSpeaker, false, [], false, $params['filter'] ?? null);
            $moderated_alternate_presentation_ids = $speaker->getAlternatePresentationIds($summit, PresentationSpeaker::RoleModerator,false, [], false, $params['filter'] ?? null);
            $values['alternate_presentations'] = array_merge($alternate_presentation_ids, $moderated_alternate_presentation_ids);
        }

        if (in_array('rejected_presentations', $relations) && !is_null($summit)) {
            $rejected_presentation_ids = $speaker->getRejectedPresentationIds($summit, PresentationSpeaker::RoleSpeaker, false, [], $params['filter'] ?? null);
            $moderated_rejected_presentation_ids = $speaker->getRejectedPresentationIds($summit, PresentationSpeaker::RoleModerator, false, [], $params['filter'] ?? null);
            $values['rejected_presentations'] = array_merge($rejected_presentation_ids, $moderated_rejected_presentation_ids);
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation =trim($relation);
                switch ($relation) {
                    case 'accepted_presentations':
                        $accepted_presentations = [];
                        foreach ($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleSpeaker, true, [], $params['filter'] ?? null) as $p) {
                            $accepted_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $moderated_accepted_presentations = [];
                        foreach ($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleModerator, true, [], $params['filter'] ?? null) as $p) {
                            $moderated_accepted_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['accepted_presentations'] = array_merge($accepted_presentations, $moderated_accepted_presentations);
                        break;
                    case 'alternate_presentations':
                        $alternate_presentations = [];
                        foreach ($speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleSpeaker, false, [], false, $params['filter'] ?? null) as $p) {
                            $alternate_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $moderated_alternate_presentations = [];
                        foreach ($speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleModerator, false, [], false, $params['filter'] ?? null) as $p) {
                            $moderated_alternate_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['alternate_presentations'] = array_merge($alternate_presentations, $moderated_alternate_presentations);
                        break;
                    case 'rejected_presentations':
                        $rejected_presentations = [];
                        foreach ($speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleSpeaker, false, [], $params['filter'] ?? null) as $p) {
                            $rejected_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $moderated_rejected_presentations = [];
                        foreach ($speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleModerator, false, [], $params['filter'] ?? null) as $p) {
                            $moderated_rejected_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['rejected_presentations'] = array_merge($rejected_presentations, $moderated_rejected_presentations);
                        break;
                }
            }
        }

        return $values;
    }
}
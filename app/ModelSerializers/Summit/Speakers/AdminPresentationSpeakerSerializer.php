<?php namespace ModelSerializers;
/**
 * Copyright 2017 OpenStack Foundation
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
 * Class AdminPresentationSpeakerSerializer
 * @package ModelSerializers
 */
final class AdminPresentationSpeakerSerializer extends PresentationSpeakerSerializer
{
    protected static $array_mappings = [
        'Notes' => 'notes:json_string',
    ];

    protected static $allowed_relations = [
        'all_presentations',
        'all_moderated_presentations',
        'affiliations',
        'registration_codes',
        'summit_assistances',
        'summit_assistance',
        'registration_code',
    ];

    protected function getMemberSerializerType():string{
        return SerializerRegistry::SerializerType_Admin;
    }

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = []) : array
    {
        if(!count($relations)) $relations  = $this->getAllowedRelations();

        $speaker                           = $this->object;
        if(!$speaker instanceof PresentationSpeaker) return [];

        $values          = parent::serialize($expand, $fields, $relations, $params);
        $summit          = isset($params['summit'])? $params['summit']:null;
        $values['email'] = JsonUtils::toJsonString($speaker->getEmail());

        if(!is_null($summit)){
            if(in_array('summit_assistance', $relations)) {
                $summit_assistance = $speaker->getAssistanceFor($summit);
                if ($summit_assistance) {
                    $values['summit_assistance'] = SerializerRegistry::getInstance()->getSerializer($summit_assistance)->serialize();
                }
            }
            if(in_array('registration_code', $relations)) {
                $registration_code = $speaker->getPromoCodeFor($summit);
                if(is_null($registration_code)){
                    $registration_code = $speaker->getDiscountCodeFor($summit);
                }
                if (!is_null($registration_code)) {
                    $values['registration_code'] = SerializerRegistry::getInstance()->getSerializer($registration_code)->serialize();
                }
            }
            if(in_array('all_presentations', $relations))
                 $values['all_presentations']           = $speaker->getPresentationIds($summit->getId() ,false);
            if(in_array('all_moderated_presentations', $relations))
                $values['all_moderated_presentations'] = $speaker->getModeratedPresentationIds($summit->getId(), false);
        }
        else{
            // get all summits info
            if(in_array('summit_assistances', $relations)) {
                $summit_assistances = [];
                foreach ($speaker->getSummitAssistances() as $assistance) {
                    $summit_assistances[] = SerializerRegistry::getInstance()->getSerializer($assistance)->serialize();
                }
                $values['summit_assistances'] = $summit_assistances;
            }

            if(in_array('registration_codes', $relations)) {
                $registration_codes = [];
                foreach ($speaker->getAssignedPromoCodes() as $promo_code) {
                    $registration_codes[] = SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize();
                }
                foreach ($speaker->getPromoCodes() as $promo_code) {
                    $registration_codes[] = SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize();
                }
                $values['registration_codes'] = $registration_codes;
            }

            if(in_array('all_presentations', $relations))
                $values['all_presentations']           = $speaker->getAllPresentationIds(false);
            if(in_array('all_moderated_presentations', $relations))
                $values['all_moderated_presentations'] = $speaker->getAllModeratedPresentationIds( false);
        }

        if(in_array('affiliations', $relations)) {
            $affiliations = [];
            if ($speaker->hasMember()) {
                $member = $speaker->getMember();
                foreach ($member->getAllAffiliations() as $affiliation) {
                    $affiliations[] = SerializerRegistry::getInstance()->getSerializer($affiliation)->serialize('organization');
                }
            }
            $values['affiliations'] = $affiliations;
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                switch (trim($relation)) {
                    case 'presentations': {
                        $presentations = [];
                        $moderated_presentations = [];
                        if(is_null($summit)){

                            foreach ($speaker->getAllPresentations( false) as $p) {
                                $presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize();
                            }

                            foreach ($speaker->getAllModeratedPresentations(false) as $p) {
                                $moderated_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize();
                            }
                        }
                        else{
                            foreach ($speaker->getPresentations($summit->getId(), false) as $p) {
                                $presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize();
                            }

                            foreach ($speaker->getModeratedPresentations($summit->getId(), false) as $p) {
                                $moderated_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize();
                            }
                        }
                        if(in_array('all_presentations', $relations))
                            $values['all_presentations']           = $presentations;
                        if(in_array('all_moderated_presentations', $relations))
                            $values['all_moderated_presentations'] = $moderated_presentations;
                    }
                    break;
                }
            }
        }
        return $values;
    }
}
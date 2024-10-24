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

use Libs\ModelSerializers\AbstractSerializer;
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

    protected static $allowed_fields = [
        'notes'
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
    
    protected function checkDataPermissions(PresentationSpeaker $speaker, array $values):array{
        return $values;
    }


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
        $speaker                           = $this->object;
        if(!$speaker instanceof PresentationSpeaker) return [];

        $values          = parent::serialize($expand, $fields, $relations, $params);
        $summit          = isset($params['summit'])? $params['summit']:null;

        if(in_array("email", $fields)) {
            $application_type = $this->resource_server_context->getApplicationType();
            // choose email serializer depending on user permissions
            // is current user is null then is a service account
            $values['email'] = $application_type == "SERVICE" ?
                JsonUtils::toNullEmail($speaker->getEmail()) :
                JsonUtils::toJsonString($speaker->getEmail());
        }

        if(!is_null($summit)){
            if(in_array('summit_assistance', $relations)) {
                $summit_assistance = $speaker->getAssistanceFor($summit);
                if ($summit_assistance) {
                    $values['summit_assistance'] = SerializerRegistry::getInstance()->getSerializer($summit_assistance)->serialize(
                        AbstractSerializer::filterExpandByPrefix($expand, 'summit_assistance'),
                        AbstractSerializer::filterFieldsByPrefix($fields, 'summit_assistance'),
                        AbstractSerializer::filterFieldsByPrefix($relations, 'summit_assistance'),
                    );
                }
            }
            if(in_array('registration_code', $relations)) {
                $registration_code = $speaker->getPromoCodeFor($summit);
                if(is_null($registration_code)){
                    $registration_code = $speaker->getDiscountCodeFor($summit);
                }
                if (!is_null($registration_code)) {
                    $values['registration_code'] = SerializerRegistry::getInstance()->getSerializer($registration_code)->serialize(
                        AbstractSerializer::filterExpandByPrefix($expand, 'registration_code'),
                        AbstractSerializer::filterFieldsByPrefix($fields, 'registration_code'),
                        AbstractSerializer::filterFieldsByPrefix($relations, 'registration_code'),
                    );
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
                    $summit_assistances[] = SerializerRegistry::getInstance()->getSerializer($assistance)->serialize(
                        AbstractSerializer::filterExpandByPrefix($expand, 'summit_assistances'),
                        AbstractSerializer::filterFieldsByPrefix($fields, 'summit_assistances'),
                        AbstractSerializer::filterFieldsByPrefix($relations, 'summit_assistances'),
                    );
                }
                $values['summit_assistances'] = $summit_assistances;
            }

            if(in_array('registration_codes', $relations)) {
                $registration_codes = [];
                foreach ($speaker->getAssignedPromoCodes() as $promo_code) {
                    $registration_codes[] = SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                        AbstractSerializer::filterExpandByPrefix($expand, 'registration_codes'),
                        AbstractSerializer::filterFieldsByPrefix($fields, 'registration_codes'),
                        AbstractSerializer::filterFieldsByPrefix($relations, 'registration_codes'),
                    );
                }
                foreach ($speaker->getPromoCodes() as $promo_code) {
                    $registration_codes[] = SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                        AbstractSerializer::filterExpandByPrefix($expand, 'registration_codes'),
                        AbstractSerializer::filterFieldsByPrefix($fields, 'registration_codes'),
                        AbstractSerializer::filterFieldsByPrefix($relations, 'registration_codes'),
                    );
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
                    $affiliations[] = SerializerRegistry::getInstance()->getSerializer($affiliation)->serialize
                    (

                        AbstractSerializer::filterExpandByPrefix($expand, 'affiliations', 'organization'),
                        AbstractSerializer::filterFieldsByPrefix($fields, 'affiliations'),
                        AbstractSerializer::filterFieldsByPrefix($relations, 'affiliations'),
                    );
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
                                $presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }

                            foreach ($speaker->getAllModeratedPresentations(false) as $p) {
                                $moderated_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                        }
                        else{
                            foreach ($speaker->getPresentations($summit->getId(), false) as $p) {
                                $presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }

                            foreach ($speaker->getModeratedPresentations($summit->getId(), false) as $p) {
                                $moderated_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
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
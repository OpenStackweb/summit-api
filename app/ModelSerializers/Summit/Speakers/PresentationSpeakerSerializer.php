<?php namespace ModelSerializers;
/**
 * Copyright 2016 OpenStack Foundation
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

use Illuminate\Support\Facades\Config;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\PresentationSpeaker;

/**
 * Class PresentationSpeakerSerializer
 * @package ModelSerializers
 */
class PresentationSpeakerSerializer extends PresentationSpeakerBaseSerializer
{

    protected static $allowed_relations = [
        'member',
        'accepted_presentations',
        'alternate_presentations',
        'rejected_presentations',
        'presentations',
        'moderated_presentations',
        'affiliations',
        'languages',
        'other_presentation_links',
        'areas_of_expertise',
        'travel_preferences',
        'active_involvements',
        'organizational_roles',
        'badge_features',
    ];

    protected function getMemberSerializerType():string {
        return SerializerRegistry::SerializerType_Public;
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
        $speaker                          = $this->object;

        if(!$speaker instanceof PresentationSpeaker) return [];

        $values                           = parent::serialize($expand, $fields, $relations, $params);
        $summit                           = isset($params['summit'])? $params['summit']:null;
        $summit_id                        = isset($params['summit_id'])? intval($params['summit_id']):null;
        $published                        = isset($params['published'])? intval($params['published']):true;

        if(!is_null($summit)) {
            $featured = $summit->getFeatureSpeaker($speaker);
            $values['featured']                = !is_null($featured);
            $values['order']                   = is_null($featured) ? 0 : $featured->getOrder();
            if(in_array('presentations', $relations))
                $values['presentations'] = $speaker->getPresentationIds($summit->getId(), $published);
            if(in_array('moderated_presentations', $relations))
                $values['moderated_presentations'] = $speaker->getModeratedPresentationIds($summit->getId(), $published);
        }

        if (in_array('member', $relations) && $speaker->hasMember())
        {
            $member              = $speaker->getMember();
            $values['gender']    = $member->getGender();
            $values['member_id'] = intval($member->getId());
            $values['member_external_id'] = intval($member->getUserExternalId());
            if(!is_null($summit_id)) {
                if(in_array('badge_features', $relations)) {
                    // check badges if the speaker user has tickets
                    $badge_features = [];
                    $already_processed_features = [];
                    foreach ($member->getPaidSummitTicketsBySummitId($summit_id) as $ticket) {
                        foreach ($ticket->getBadgeFeatures() as $feature) {
                            if (in_array($feature->getId(), $already_processed_features)) continue;
                            $already_processed_features[] = $feature->getId();
                            $badge_features[] = SerializerRegistry::getInstance()->getSerializer($feature)->serialize(
                                AbstractSerializer::filterExpandByPrefix($expand, 'badge_features'),
                                AbstractSerializer::filterFieldsByPrefix($fields, 'badge_features'),
                                AbstractSerializer::filterFieldsByPrefix($relations, 'badge_features'),
                            );
                        }
                    }
                    $values['badge_features'] = $badge_features;
                }
            }
        }

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

        if(in_array('affiliations', $relations)) {
            $affiliations = [];
            if ($speaker->hasMember()) {
                $member = $speaker->getMember();
                foreach ($member->getCurrentAffiliations() as $affiliation) {
                    $affiliations[] = SerializerRegistry::getInstance()->getSerializer($affiliation)->serialize(
                        AbstractSerializer::filterExpandByPrefix($expand, 'affiliations', 'organization'),
                        AbstractSerializer::filterFieldsByPrefix($fields, 'affiliations'),
                        AbstractSerializer::filterFieldsByPrefix($relations, 'affiliations'),
                    );
                }
            }
            $values['affiliations'] = $affiliations;
        }

        if(in_array('languages', $relations)) {
            $languages = [];
            foreach ($speaker->getLanguages() as $language) {
                $languages[] = SerializerRegistry::getInstance()->getSerializer($language)->serialize(
                    AbstractSerializer::filterExpandByPrefix($expand, 'languages'),
                    AbstractSerializer::filterFieldsByPrefix($fields, 'languages'),
                    AbstractSerializer::filterFieldsByPrefix($relations, 'languages'),
                );
            }
            $values['languages'] = $languages;
        }

        if(in_array('other_presentation_links', $relations)) {
            $other_presentation_links = [];
            foreach ($speaker->getOtherPresentationLinks() as $link) {
                $other_presentation_links[] = SerializerRegistry::getInstance()->getSerializer($link)->serialize
                (
                    AbstractSerializer::filterExpandByPrefix($expand, 'other_presentation_links'),
                    AbstractSerializer::filterFieldsByPrefix($fields, 'other_presentation_links'),
                    AbstractSerializer::filterFieldsByPrefix($relations, 'other_presentation_links'),
                );
            }
            $values['other_presentation_links'] = $other_presentation_links;
        }

        if(in_array('areas_of_expertise', $relations)) {
            $areas_of_expertise = [];
            foreach ($speaker->getAreasOfExpertise() as $exp) {
                $areas_of_expertise[] = SerializerRegistry::getInstance()->getSerializer($exp)->serialize
                (
                    AbstractSerializer::filterExpandByPrefix($expand, 'areas_of_expertise'),
                    AbstractSerializer::filterFieldsByPrefix($fields, 'areas_of_expertise'),
                    AbstractSerializer::filterFieldsByPrefix($relations, 'areas_of_expertise'),
                );
            }
            $values['areas_of_expertise'] = $areas_of_expertise;
        }

        if(in_array('travel_preferences', $relations)) {
            $travel_preferences = [];
            foreach ($speaker->getTravelPreferences() as $tp) {
                $travel_preferences[] = SerializerRegistry::getInstance()->getSerializer($tp)->serialize
                (
                    AbstractSerializer::filterExpandByPrefix($expand, 'travel_preferences'),
                    AbstractSerializer::filterFieldsByPrefix($fields, 'travel_preferences'),
                    AbstractSerializer::filterFieldsByPrefix($relations, 'travel_preferences'),
                );
            }
            $values['travel_preferences'] = $travel_preferences;
        }

        if(in_array('active_involvements', $relations)) {
            $active_involvements = [];
            foreach ($speaker->getActiveInvolvements() as $ai) {
                $active_involvements[] = SerializerRegistry::getInstance()->getSerializer($ai)->serialize
                (
                    AbstractSerializer::filterExpandByPrefix($expand, 'active_involvements'),
                    AbstractSerializer::filterFieldsByPrefix($fields, 'active_involvements'),
                    AbstractSerializer::filterFieldsByPrefix($relations, 'active_involvements'),
                );
            }
            $values['active_involvements'] = $active_involvements;
        }

        if(in_array('organizational_roles', $relations)) {
            $organizational_roles = [];
            foreach ($speaker->getOrganizationalRoles() as $or) {
                $organizational_roles[] = SerializerRegistry::getInstance()->getSerializer($or)->serialize(
                    AbstractSerializer::filterExpandByPrefix($expand, 'organizational_roles'),
                    AbstractSerializer::filterFieldsByPrefix($fields, 'organizational_roles'),
                    AbstractSerializer::filterFieldsByPrefix($relations, 'organizational_roles'),
                );
            }
            $values['organizational_roles'] = $organizational_roles;
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation =trim($relation);
                switch ($relation) {
                    case 'presentations': {
                        // if summit_id is null then all presentations
                        $presentations = [];
                        foreach ($speaker->getPresentations($summit_id, $published) as $p) {
                            $presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $values['presentations'] = $presentations;

                        $moderated_presentations = [];
                        foreach ($speaker->getModeratedPresentations($summit_id, $published) as $p) {
                            $moderated_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $values['moderated_presentations'] = $moderated_presentations;
                    }
                        break;
                    case 'member': {
                        if($speaker->hasMember()){
                            unset($values['member_id']);
                            $values['member'] =  SerializerRegistry::getInstance()->getSerializer
                            (
                                $speaker->getMember(),
                                $this->getMemberSerializerType()
                            )->serialize(
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                    }
                        break;
                    case 'accepted_presentations': {
                        $accepted_presentations = [];
                        foreach ($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleSpeaker, true, [], $params['filter'] ?? null) as $p) {
                            $accepted_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $moderated_accepted_presentations = [];
                        foreach ($speaker->getAcceptedPresentations($summit, PresentationSpeaker::RoleModerator, true, [], $params['filter'] ?? null) as $p) {
                            $moderated_accepted_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $values['accepted_presentations'] = array_merge($accepted_presentations, $moderated_accepted_presentations);
                    }
                        break;
                    case 'alternate_presentations': {
                        $alternate_presentations = [];
                        foreach ($speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleSpeaker, false, [], false, $params['filter'] ?? null) as $p) {
                            $alternate_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $moderated_alternate_presentations = [];
                        foreach ($speaker->getAlternatePresentations($summit, PresentationSpeaker::RoleModerator, false, [], false, $params['filter'] ?? null) as $p) {
                            $moderated_alternate_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $values['alternate_presentations'] = array_merge($alternate_presentations, $moderated_alternate_presentations);
                    }
                        break;
                    case 'rejected_presentations': {
                        $rejected_presentations = [];
                        foreach ($speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleSpeaker, false, [], $params['filter'] ?? null) as $p) {
                            $rejected_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $moderated_rejected_presentations = [];
                        foreach ($speaker->getRejectedPresentations($summit, PresentationSpeaker::RoleModerator, false, [], $params['filter'] ?? null) as $p) {
                            $moderated_rejected_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $values['rejected_presentations'] = array_merge($rejected_presentations, $moderated_rejected_presentations);
                    }
                        break;
                }
            }
        }

       return $this->checkDataPermissions($speaker, $values);
    }

    /**
     * @param PresentationSpeaker $speaker
     * @param array $values
     * @return array
     */
    protected function checkDataPermissions(PresentationSpeaker $speaker, array $values):array{
        // permissions check

        if(!$speaker->isPublicProfileShowBio())
        {
            if(isset($values['bio'])) $values['bio'] = '';
            if(isset($values['gender'])) $values['gender'] = '';
            if(isset($values['company'])) $values['company'] = '';
            if(isset($values['state'])) $values['state'] = '';
            if(isset($values['country'])) $values['country'] = '';
            if(isset($values['title'])) $values['title'] = '';

            if(isset($values['affiliations'])) $values['affiliations'] = [];
            if(isset($values['languages'])) $values['languages'] = [];
            if(isset($values['other_presentation_links'])) $values['other_presentation_links'] = [];
            if(isset($values['areas_of_expertise'])) $values['areas_of_expertise'] = [];
            if(isset($values['travel_preferences'])) $values['travel_preferences'] = [];
            if(isset($values['active_involvements'])) $values['active_involvements'] = [];
            if(isset($values['organizational_roles'])) $values['organizational_roles'] = [];
            if(isset($values['badge_features'])) $values['badge_features'] = [];
        }

        if(!$speaker->isPublicProfileShowEmail())
        {
            if(isset($values['email'])) $values['email'] = '';
        }

        if(!$speaker->isPublicProfileShowSocialMediaInfo())
        {
            if(isset($values['irc'])) $values['irc'] = '';
            if(isset($values['twitter'])) $values['twitter'] = '';
        }

        if(!$speaker->isPublicProfileShowPhoto())
        {
            if(isset($values['pic'])) $values['pic'] = Config::get("app.default_profile_image", null);
            if(isset($values['big_pic'])) $values['big_pic'] = Config::get("app.default_profile_image", null);
        }

        if(!$speaker->isPublicProfileShowFullname())
        {
            if(isset($values['last_name'])) $values['last_name'] = '';
        }

        if(!$speaker->isPublicProfileShowTelephoneNumber())
        {
            if(isset($values['phone_number'])) $values['phone_number'] = '';
        }

        return $values;
    }
}
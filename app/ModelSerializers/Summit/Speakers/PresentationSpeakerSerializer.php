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
class PresentationSpeakerSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'FirstName'               => 'first_name:json_string',
        'LastName'                => 'last_name:json_string',
        'Title'                   => 'title:json_string',
        'Bio'                     => 'bio:json_string',
        'IRCHandle'               => 'irc:json_string',
        'TwitterName'             => 'twitter:json_string',
        'OrgHasCloud'             => 'org_has_cloud:json_boolean',
        'Country'                 => 'country:json_string',
        'AvailableForBureau'      => 'available_for_bureau:json_boolean',
        'FundedTravel'            => 'funded_travel:json_boolean',
        'WillingToTravel'         => 'willing_to_travel:json_boolean',
        'WillingToPresentVideo'   => 'willing_to_present_video:json_boolean',
        'Email'                   => 'email:json_obfuscated_email',
        'MemberID'                => 'member_id:json_int',
        'RegistrationRequestId'   => 'registration_request_id:json_int',
        'ProfilePhotoUrl'         => 'pic:json_url',
        'BigProfilePhotoUrl'      => 'big_pic:json_url',
        'Company'                 => 'company:json_string',
        'PhoneNumber'             => 'phone_number:json_string',
    ];

    protected static $allowed_relations = [
        'member',
    ];

    protected function getMemberSerializerType():string{
        return SerializerRegistry::SerializerType_Public;
    }

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [] )
    {
        if(!count($relations)) $relations  = $this->getAllowedRelations();
        $speaker                           = $this->object;

        if(!$speaker instanceof PresentationSpeaker) return [];

        $values                            = parent::serialize($expand, $fields, $relations, $params);
        $summit_id                         = isset($params['summit_id'])? intval($params['summit_id']):null;
        $published                         = isset($params['published'])? intval($params['published']):true;

        if(!is_null($summit_id)) {
            $values['presentations']           = $speaker->getPresentationIds($summit_id, $published);
            $values['moderated_presentations'] = $speaker->getModeratedPresentationIds($summit_id, $published);
        }

        if (in_array('member', $relations) && $speaker->hasMember())
        {
            $member              = $speaker->getMember();
            $values['gender']    = $member->getGender();
            $values['member_id'] = intval($member->getId());
            $values['member_external_id'] = intval($member->getUserExternalId());
            if(!is_null($summit_id)) {
                // check badges if the speaker user has tickets
                $badge_features = [];
                $already_processed_features= [];
                foreach($member->getPaidSummitTicketsBySummitId($summit_id) as $ticket){
                    foreach($ticket->getBadgeFeatures() as $feature) {
                        if(in_array($feature->getId(), $already_processed_features)) continue;
                        $already_processed_features[] = $feature->getId();
                        $badge_features[] = SerializerRegistry::getInstance()->getSerializer($feature)->serialize();
                    }
                }
                $values['badge_features'] = $badge_features;
            }
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

        $affiliations = [];
        if($speaker->hasMember()) {
            $member = $speaker->getMember();
            foreach ($member->getCurrentAffiliations() as $affiliation) {
                $affiliations[] = SerializerRegistry::getInstance()->getSerializer($affiliation)->serialize('organization');
            }
        }
        $values['affiliations'] = $affiliations;

        $languages = [];
        foreach ($speaker->getLanguages() as $language){
            $languages[] = SerializerRegistry::getInstance()->getSerializer($language)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'languages'));
        }
        $values['languages'] = $languages;

        $other_presentation_links = [];
        foreach ($speaker->getOtherPresentationLinks() as $link){
            $other_presentation_links[] = SerializerRegistry::getInstance()->getSerializer($link)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'other_presentation_links'));
        }
        $values['other_presentation_links'] = $other_presentation_links;

        $areas_of_expertise = [];
        foreach ($speaker->getAreasOfExpertise() as $exp){
            $areas_of_expertise[] = SerializerRegistry::getInstance()->getSerializer($exp)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'areas_of_expertise'));
        }
        $values['areas_of_expertise'] = $areas_of_expertise;

        $travel_preferences = [];
        foreach ($speaker->getTravelPreferences() as $tp){
            $travel_preferences[] = SerializerRegistry::getInstance()->getSerializer($tp)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'travel_preferences'));
        }
        $values['travel_preferences'] = $travel_preferences;

        $active_involvements = [];
        foreach ($speaker->getActiveInvolvements() as $ai){
            $active_involvements[] = SerializerRegistry::getInstance()->getSerializer($ai)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'active_involvements'));
        }
        $values['active_involvements'] = $active_involvements;

        $organizational_roles = [];
        foreach ($speaker->getOrganizationalRoles() as $or){
            $organizational_roles[] = SerializerRegistry::getInstance()->getSerializer($or)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'organizational_roles'));
        }
        $values['organizational_roles'] = $organizational_roles;

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation =trim($relation);
                switch ($relation) {
                    case 'presentations': {
                        // if summit_id is null then all presentations
                        $presentations = [];
                        foreach ($speaker->getPresentations($summit_id, $published) as $p) {
                            $presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['presentations'] = $presentations;

                        $moderated_presentations = [];
                        foreach ($speaker->getModeratedPresentations($summit_id, $published) as $p) {
                            $moderated_presentations[] = SerializerRegistry::getInstance()->getSerializer($p)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
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
                           )->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                       }
                    }
                    break;
                }
            }
        }

        return $values;
    }
}
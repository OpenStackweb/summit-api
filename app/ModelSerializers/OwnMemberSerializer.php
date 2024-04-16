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
use models\main\Member;
/**
 * Class OwnMemberSerializer
 * @package ModelSerializers
 */
final class OwnMemberSerializer extends AbstractMemberSerializer
{

    protected static $array_mappings = [
        'FirstName'       => 'first_name:json_string',
        'LastName'        => 'last_name:json_string',
        'Gender'          => 'gender:json_string',
        'GitHubUser'      => 'github_user:json_string',
        'Bio'             => 'bio:json_string',
        'LinkedInProfile' => 'linked_in:json_string',
        'IrcHandle'       => 'irc:json_string',
        'TwitterHandle'   => 'twitter:json_string',
        'State'           => 'state:json_string',
        'Country'         => 'country:json_string',
        'Active'          => 'active:json_boolean',
        'EmailVerified'   => 'email_verified:json_boolean',
        'Email'           => 'email:json_string',
        'Projects'        => 'projects:json_string_array',
        'OtherProject'    => 'other_project:json_string',
        'DisplayOnSite'   => 'display_on_site:json_boolean',
        'SubscribedToNewsletter' => 'subscribed_to_newsletter:json_boolean',
        'ShirtSize' => 'shirt_size:json_string',
        'FoodPreference' => 'food_preference:json_string_array',
        'OtherFoodPreference' => 'other_food_preference:json_string',
    ];

    protected static $allowed_relations = [
        'team_memberships',
        'groups_events',
        'favorite_summit_events',
        'feedback',
        'schedule_summit_events',
        'summit_tickets',
        'rsvp',
        'sponsor_memberships',
        'legal_agreements',
        'track_chairs',
        'schedule_shareable_link',
        'summit_permission_groups',
    ];

    private static $expand_group_events = [
        'type',
        'location',
        'sponsors',
        'track',
        'track_groups',
        'groups',
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
        $member         = $this->object;
        if(!$member instanceof Member) return [];

        $values           = parent::serialize($expand, $fields, $relations, $params);
        $summit           = isset($params['summit'])? $params['summit'] :null;
        $speaker          = !is_null($summit)? $summit->getSpeakerByMember($member): null;
        $attendee         = !is_null($summit)? $summit->getAttendeeByMember($member): null;
        $groups_events    = !is_null($summit)? $summit->getGroupEventsFor($member): null;

        if(!is_null($speaker))
            $values['speaker_id'] = $speaker->getId();

        if(!is_null($attendee))
            $values['attendee_id'] = $attendee->getId();

        if(!is_null($groups_events) && in_array('groups_events', $relations)){
            $res = [];
            foreach ($groups_events as $group_event){
                $res[] = SerializerRegistry::getInstance()
                    ->getSerializer($group_event)
                    ->serialize(implode(',', self::$expand_group_events));
            }
            $values['groups_events'] = $res;
        }

        if(in_array('team_memberships', $relations)){
            $res = [];
            foreach ($member->getTeamMemberships() as $team_membership){
                $res[] = SerializerRegistry::getInstance()
                    ->getSerializer($team_membership)
                    ->serialize('team,team.member');
            }
            $values['team_memberships'] = $res;
        }

        if(in_array('sponsor_memberships', $relations)){
            $res = [];
            $sponsorship_expand = AbstractSerializer::filterExpandByPrefix($expand, "sponsor_memberships");
            if(empty($sponsorship_expand)) $sponsorship_expand = 'summit,company,sponsorship';

            foreach ($member->getActiveSummitsSponsorMemberships() as $sponsor_membership){
                $res[] = SerializerRegistry::getInstance()
                    ->getSerializer($sponsor_membership)
                    ->serialize($sponsorship_expand, [], [], ['member' => $member]);
            }
            $values['sponsor_memberships'] = $res;
        }

        if(in_array('favorite_summit_events', $relations) && !is_null($summit)){
            $res = [];
            foreach ($member->getFavoritesEventsIds($summit) as $event_id){
                $res[] = intval($event_id);
            }
            $values['favorite_summit_events'] = $res;
        }

        if(in_array('schedule_summit_events', $relations) && !is_null($summit)){
            $schedule = [];

            foreach ($member->getScheduledEventsIds($summit) as $event_id){
                $schedule[] = intval($event_id);
            }

            $values['schedule_summit_events'] = $schedule;
        }

        if(in_array('summit_tickets', $relations) && !is_null($summit)){
            $res = [];
            $count = 0;
            foreach ($member->getPaidSummitTicketsIds($summit) as $ticket_id){
                $res[] = intval($ticket_id);
                $count++;
                /*if (AbstractSerializer::MaxCollectionPage < $count) {
                    $values['summit_tickets_has_more'] = true;
                    break;
                }*/
            }
            $values['summit_tickets'] = $res;
        }

        if(in_array('schedule_shareable_link', $relations) && !is_null($summit)){
            $link = $member->getScheduleShareableLinkBy($summit);
            if(!is_null($link)) {
                $values['schedule_shareable_link'] = SerializerRegistry::getInstance()
                    ->getSerializer($link)->serialize();
            }
        }

        if(in_array('legal_agreements', $relations)){
            $res = [];
            foreach ($member->getLegalAgreements() as $agreement)
                $res[] = intval($agreement->getId());
            $values['legal_agreements'] = $res;
        }

        if(in_array('track_chairs', $relations)){
            $res = [];
            foreach ($member->getTrackChairs() as $track_chair){
                $res[] = intval($track_chair->getId());
            }
            $values['track_chairs'] = $res;
        }

        if(in_array('summit_permission_groups', $relations)){
            $res = [];
            foreach ($member->getSummitAdministratorPermissionGroup() as $permissionGroup){
                $res[] = intval($permissionGroup->getId());
            }
            $values['summit_permission_groups'] = $res;
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'attendee': {
                        if (!is_null($attendee))
                        {
                            unset($values['attendee_id']);
                            $values['attendee'] = SerializerRegistry::getInstance()
                                ->getSerializer($attendee)
                                ->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                );
                        }
                    }
                    break;
                    case 'speaker': {
                        if (!is_null($speaker))
                        {
                            unset($values['speaker_id']);
                            $values['speaker'] = SerializerRegistry::getInstance()->getSerializer($speaker)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation), [], ['none']
                            );
                        }
                    }
                    break;
                    case 'feedback': {
                        if(!in_array('feedback', $relations)) break;
                        if(is_null($summit)) break;
                        $feedback = array();
                        foreach ($member->getFeedbackBySummit($summit) as $f) {
                            $feedback[] = SerializerRegistry::getInstance()->getSerializer($f)->serialize(
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                $params
                            );
                        }
                        $values['feedback'] = $feedback;
                    }
                    break;
                    case 'favorite_summit_events':{
                        if(!in_array('favorite_summit_events', $relations)) break;
                        if(is_null($summit)) break;
                        $favorites = [];
                        foreach ($member->getFavoritesSummitEventsBySummit($summit) as $events){
                            $favorites[] = SerializerRegistry::getInstance()
                                ->getSerializer($events)
                                ->serialize(
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                        }
                        $values['favorite_summit_events'] = $favorites;
                    }
                    case 'schedule_summit_events':{
                        if(!in_array('schedule_summit_events', $relations)) break;
                        if(is_null($summit)) break;
                        $schedule = [];
                        foreach ($member->getScheduleBySummit($summit) as $events){
                            $schedule[] = SerializerRegistry::getInstance()
                                ->getSerializer($events)
                                ->serialize(
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                        }
                        $values['schedule_summit_events'] = $schedule;
                    }
                    break;
                    case 'summit_tickets':{
                        if(!in_array('summit_tickets', $relations)) break;
                        if(is_null($summit)) break;
                        $summit_tickets = [];
                        $count = 0;
                        foreach ($member->getPaidSummitTickets($summit) as $ticket){
                            $summit_tickets[] = SerializerRegistry::getInstance()
                                ->getSerializer($ticket)
                                ->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            $count++;
                            /*if (AbstractSerializer::MaxCollectionPage < $count){
                                $values['summit_tickets_has_more'] = true;
                                break;
                            }*/
                        }
                        $values['summit_tickets'] = $summit_tickets;
                    }
                        break;
                    case 'rsvp':{
                        if(!in_array('rsvp', $relations)) break;
                        if(is_null($summit)) break;
                        $rsvps = [];
                        foreach ($member->getRsvpBySummit($summit) as $rsvp){
                            $rsvps[] = SerializerRegistry::getInstance()
                                ->getSerializer($rsvp)
                                ->serialize(
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                        }
                        $values['rsvp'] = $rsvps;
                    }
                    break;
                    case 'legal_agreements':{
                        if(!in_array('legal_agreements', $relations)) break;
                        $res = [];
                        foreach ($member->getLegalAgreements() as $agreement){
                            $res[] = SerializerRegistry::getInstance()
                                ->getSerializer($agreement)
                                ->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                        }
                        $values['legal_agreements'] = $res;
                    }
                        break;
                    case 'track_chairs':{
                        if(!in_array('track_chairs', $relations)) break;
                        $res = [];
                        foreach ($member->getTrackChairs() as $trackChair){
                            $res[] = SerializerRegistry::getInstance()
                                ->getSerializer($trackChair)
                                ->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                        }
                        $values['track_chairs'] = $res;
                    }
                        break;
                    case 'summit_permission_groups':{
                        if(!in_array('summit_permission_groups', $relations)) break;
                        $res = [];
                        foreach ($member->getSummitAdministratorPermissionGroup() as $permissionGroup){
                            $res[] = SerializerRegistry::getInstance()
                                ->getSerializer($permissionGroup)
                                ->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    ['summits'],
                                    $params
                                );
                        }
                        $values['summit_permission_groups'] = $res;
                    }
                        break;
                }
            }
        }
        return $values;
    }
}
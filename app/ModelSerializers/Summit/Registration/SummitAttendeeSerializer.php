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

use App\ModelSerializers\Traits\RequestScopedCache;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitAttendee;
/**
 * Class SummitAttendeeSerializer
 * @package ModelSerializers
 */
class SummitAttendeeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'SummitHallCheckedIn'     => 'summit_hall_checked_in:json_boolean',
        'SummitHallCheckedInDate' => 'summit_hall_checked_in_date:datetime_epoch',
        'SummitVirtualCheckedInDate' => 'summit_virtual_checked_in_date:datetime_epoch',
        'SharedContactInfo'       => 'shared_contact_info:json_boolean',
        'MemberId'                => 'member_id:json_int',
        'SummitId'                => 'summit_id:json_int',
        'FirstName'               => 'first_name:json_string',
        'Surname'                 => 'last_name:json_string',
        'Email'                   => 'email:json_string',
        'CompanyName'             => 'company:json_string',
        'CompanyId'               => 'company_id:json_int',
        'DisclaimerAcceptedDate'  => 'disclaimer_accepted_date:datetime_epoch',
        'DisclaimerAccepted'      => 'disclaimer_accepted:json_boolean',
        'Status'                  => 'status:json_string',
        'ManagerId'               => 'manager_id:json_int',
    ];

    protected function getMemberSerializer(array $params):string{
        if(isset($params['serializer_type']))
           return $params['serializer_type'];
        return SerializerRegistry::SerializerType_Public;
    }

    protected static $allowed_relations = [
        'extra_questions',
        'tickets',
        'presentation_votes',
        'ticket_types',
        'allowed_access_levels',
        'allowed_features',
        'tags'
    ];

    use RequestScopedCache;

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {

        return $this->cache($this->getRequestKey
        (
            "SummitAttendeeSerializer",
            $this->object->getIdentifier(),
            $expand,
            $fields,
            $relations
        ), function () use ($expand, $fields, $relations, $params) {

            $attendee = $this->object;
            if(!$attendee instanceof SummitAttendee) return [];
            $serializer_type = SerializerRegistry::SerializerType_Public;

            if(isset($params['serializer_type']))
                $serializer_type = $params['serializer_type'];
            $summit         = $attendee->getSummit();

            $beginVotingDate = $params['begin_attendee_voting_period_date'] ?? null;
            $endVotingDate   = $params['end_attendee_voting_period_date'] ?? null;
            $track_group_id  = $params['presentation_votes_track_group_id'] ?? null;
            $values          = parent::serialize($expand, $fields, $relations, $params);
            $member          = null;
            $speaker         = null;

            if (in_array('tickets', $relations)) {
                $count = 0;
                $tickets = [];
                foreach ($attendee->getTickets() as $t) {
                    if (!$t->hasTicketType()) continue;
                    if ($t->isCancelled()) continue;
                    if (!$t->isActive()) continue;
                    $tickets[] = intval($t->getId());
                    $count++;
                    /*if (AbstractSerializer::MaxCollectionPage < $count) {
                        $values['tickets_has_more'] = true;
                        break;
                    }*/
                }
                $values['tickets'] = $tickets;
            }

            if (in_array('extra_questions', $relations)) {
                $extra_question_answers = [];

                foreach ($attendee->getExtraQuestionAnswers() as $answer) {
                    $extra_question_answers[] = $answer->getId();
                }
                $values['extra_questions'] = $extra_question_answers;
            }

            if (in_array('presentation_votes', $relations)) {
                $presentation_votes = [];

                foreach ($attendee->getPresentationVotes($beginVotingDate, $endVotingDate, $track_group_id) as $vote) {
                    $presentation_votes[] = $vote->getId();
                }
                $values['presentation_votes'] = $presentation_votes;
            }

            if($attendee->hasMember())
            {
                $member               = $attendee->getMember();
                $values['member_id']  = $member->getId();
                $speaker              = $summit->getSpeakerByMember($member);
                if (!is_null($speaker)) {
                    $values['speaker_id'] = intval($speaker->getId());
                }
            }


            if(!count($fields) || in_array('votes_count', $fields))
                $values['votes_count'] = $attendee->getVotesCount($beginVotingDate, $endVotingDate, $track_group_id);

            if (in_array('ticket_types', $relations)) {
                $values['ticket_types'] = $attendee->getBoughtTicketTypes();
            }

            if (in_array('allowed_access_levels', $relations)) {
                $allowed_access_levels = [];
                foreach($attendee->getAllowedAccessLevels() as $al){
                    $allowed_access_levels[] = $al->getId();
                }
                $values['allowed_access_levels'] = $allowed_access_levels;
            }

            if (in_array('allowed_features', $relations)) {
                $allowed_features = [];
                foreach($attendee->getAllowedBadgeFeatures() as $f){
                    $allowed_features[] = $f->getId();
                }
                $values['allowed_features'] = $allowed_features;
            }

            if (in_array('tags', $relations)) {
                $tags = [];
                foreach($attendee->getTags() as $t){
                    $tags[] = $t->getId();
                }
                $values['tags'] = $tags;
            }

            if (!empty($expand)) {
                $exp_expand = explode(',', $expand);
                foreach ($exp_expand as $relation) {
                    $relation = trim($relation);
                    switch ($relation) {
                        case 'tickets': {
                            if (!in_array('tickets', $relations)) break;
                            unset($values['tickets']);
                            $tickets = [];
                            $count = 0;
                            foreach($attendee->getTickets() as $t)
                            {
                                if (!$t->hasTicketType()) continue;
                                if ($t->isCancelled()) continue;
                                if (!$t->isActive()) continue;
                                $tickets[] = SerializerRegistry::getInstance()->getSerializer($t)->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                                $count++;
                                /*if (AbstractSerializer::MaxCollectionPage < $count) {
                                    $values['tickets_has_more'] = true;
                                    break;
                                }*/
                            }
                            $values['tickets'] = $tickets;
                        }
                            break;
                        case 'extra_questions': {
                            if (!in_array('extra_questions', $relations)) break;
                            unset($values['extra_questions']);
                            $extra_question_answers = [];
                            foreach($attendee->getExtraQuestionAnswers() as $answer)
                            {
                                $extra_question_answers[] = SerializerRegistry::getInstance()->getSerializer($answer)->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                            $values['extra_questions'] = $extra_question_answers;
                        }
                            break;
                        case 'presentation_votes': {
                            if (!in_array('presentation_votes', $relations)) break;
                            unset($values['presentation_votes']);
                            $presentation_votes = [];
                            foreach($attendee->getPresentationVotes($beginVotingDate, $endVotingDate, $track_group_id) as $vote)
                            {
                                $presentation_votes[] = SerializerRegistry::getInstance()->getSerializer($vote)
                                    ->serialize(
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                        $params
                                    );
                            }
                            $values['presentation_votes'] = $presentation_votes;
                        }
                            break;
                        case 'allowed_features':{
                            if (!in_array('allowed_features', $relations)) break;
                            unset($values['allowed_features']);
                            $allowed_features = [];
                            foreach($attendee->getAllowedBadgeFeatures() as $f){
                                $allowed_features[] = SerializerRegistry::getInstance()
                                    ->getSerializer($f)
                                    ->serialize(
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                        $params
                                    );
                            }
                            $values['allowed_features'] = $allowed_features;
                        }
                            break;
                        case 'allowed_access_levels':{
                            if (!in_array('allowed_access_levels', $relations)) break;
                            unset($values['allowed_access_levels']);
                            $allowed_access_levels = [];
                            foreach($attendee->getAllowedAccessLevels() as $al){
                                $allowed_access_levels[] = SerializerRegistry::getInstance()
                                    ->getSerializer($al)
                                    ->serialize(
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                        $params
                                    );
                            }
                            $values['allowed_access_levels'] = $allowed_access_levels;
                        }
                            break;
                        case 'speaker': {
                            if (!is_null($speaker))
                            {
                                unset($values['speaker_id']);
                                $values['speaker'] = SerializerRegistry::getInstance()->getSerializer($speaker)->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                        }
                            break;
                        case 'member':{
                            if($attendee->hasMember())
                            {
                                unset($values['member_id']);
                                $values['member']    = SerializerRegistry::getInstance()
                                    ->getSerializer($attendee->getMember(), $this->getMemberSerializer($params))
                                    ->serialize(
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                        ['summit' => $attendee->getSummit()]);
                            }
                        }
                            break;
                        case 'company':
                            {

                                if ($attendee->hasCompany()) {
                                    unset($values['company_id']);
                                    $values['company'] = SerializerRegistry::getInstance()->getSerializer($attendee->getCompany())->serialize
                                    (
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                        $params
                                    );
                                }
                            }
                            break;
                        case 'manager':{
                            if($attendee->hasManager()){
                                unset($values['manager_id']);
                                $values['manager'] = SerializerRegistry::getInstance()->getSerializer($attendee->getManager())->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                        }
                        break;
                        case 'tags':
                            if (!in_array('tags', $relations)) break;
                            unset($values['tags']);
                            $tags = [];
                            foreach($attendee->getTags() as $t){
                                $tags[] = SerializerRegistry::getInstance()
                                    ->getSerializer($t)
                                    ->serialize(
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                        $params
                                    );
                            }
                            $values['tags'] = $tags;
                    }
                }
            }

            return $values;
        });

    }
}

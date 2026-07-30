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
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
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

            $beginVotingDate = $params['begin_attendee_voting_period_date'] ?? null;
            $endVotingDate   = $params['end_attendee_voting_period_date'] ?? null;
            $track_group_id  = $params['presentation_votes_track_group_id'] ?? null;

            // parent::serialize() already runs _expand() internally (using $expand_mappings),
            // so a matching expand token sets the full-object key (`tickets`, `member`, ...)
            // right here, before any of the id-list fallbacks below run. Each fallback below
            // guards with !isset(...) - same pattern as OpenStackReleaseSerializer::serialize()
            // - so it only fires when the field wasn't already expanded.
            $values          = parent::serialize($expand, $fields, $relations, $params);
            $member          = null;
            $speaker         = null;

            if (in_array('tickets', $relations) && !isset($values['tickets'])) {
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

            if (in_array('extra_questions', $relations) && !isset($values['extra_questions'])) {
                $extra_question_answers = [];

                foreach ($attendee->getExtraQuestionAnswers() as $answer) {
                    $extra_question_answers[] = $answer->getId();
                }
                $values['extra_questions'] = $extra_question_answers;
            }

            if (in_array('presentation_votes', $relations) && !isset($values['presentation_votes'])) {
                $presentation_votes = [];

                foreach ($attendee->getPresentationVotes($beginVotingDate, $endVotingDate, $track_group_id) as $vote) {
                    $presentation_votes[] = $vote->getId();
                }
                $values['presentation_votes'] = $presentation_votes;
            }

            if($attendee->hasMember())
            {
                $member  = $attendee->getMember();
                $speaker = $attendee->getSummit()->getSpeakerByMember($member);
                if (!isset($values['member'])) $values['member_id'] = $member->getId();
                if (!is_null($speaker) && !isset($values['speaker'])) {
                    $values['speaker_id'] = intval($speaker->getId());
                }
            }


            if(!count($fields) || in_array('votes_count', $fields))
                $values['votes_count'] = $attendee->getVotesCount($beginVotingDate, $endVotingDate, $track_group_id);

            if (in_array('ticket_types', $relations)) {
                $values['ticket_types'] = $attendee->getBoughtTicketTypes();
            }

            if (in_array('allowed_access_levels', $relations) && !isset($values['allowed_access_levels'])) {
                $allowed_access_levels = [];
                foreach($attendee->getAllowedAccessLevels() as $al){
                    $allowed_access_levels[] = $al->getId();
                }
                $values['allowed_access_levels'] = $allowed_access_levels;
            }

            if (in_array('allowed_features', $relations) && !isset($values['allowed_features'])) {
                $allowed_features = [];
                foreach($attendee->getAllowedBadgeFeatures() as $f){
                    $allowed_features[] = $f->getId();
                }
                $values['allowed_features'] = $allowed_features;
            }

            if (in_array('tags', $relations) && !isset($values['tags'])) {
                $tags = [];
                foreach($attendee->getTags() as $t){
                    $tags[] = $t->getId();
                }
                $values['tags'] = $tags;
            }

            return $values;
        });

    }

    public static function shouldSkipTicket($ticket, array $params): bool
    {
        if (!$ticket->hasTicketType()) return true;
        if ($ticket->isCancelled()) return true;
        if (!$ticket->isActive()) return true;
        return false;
    }

    protected static $expand_mappings = [
        'summit' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'summit_id',
            'getter' => 'getSummit',
            'has' => 'hasSummit',
        ],
        'company' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'company_id',
            'getter' => 'getCompany',
            'has' => 'hasCompany',
        ],
        'manager' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'manager_id',
            'getter' => 'getManager',
            'has' => 'hasManager',
        ],
        'member' => [
            'type' => SummitAttendeeMemberExpandSerializer::class,
            'original_attribute' => 'member_id',
            'getter' => 'getMember',
            'has' => 'hasMember',
            'serializer_type' => SerializerRegistry::SerializerType_Public,
        ],
        'speaker' => [
            'type' => SummitAttendeeSpeakerExpandSerializer::class,
            'original_attribute' => 'speaker_id',
            'getter' => 'getMember',
        ],
        'tickets' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getTickets',
            'should_verify_relation' => true,
            'should_skip_rule' => 'ModelSerializers\\SummitAttendeeSerializer::shouldSkipTicket',
        ],
        'extra_questions' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getExtraQuestionAnswers',
            'should_verify_relation' => true,
        ],
        'presentation_votes' => [
            'type' => SummitAttendeePresentationVotesExpandSerializer::class,
            'getter' => 'getPresentationVotes',
            'should_verify_relation' => true,
        ],
        'allowed_access_levels' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getAllowedAccessLevels',
            'should_verify_relation' => true,
        ],
        'allowed_features' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getAllowedBadgeFeatures',
            'should_verify_relation' => true,
        ],
        'tags' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getTags',
            'should_verify_relation' => true,
        ],
    ];
}

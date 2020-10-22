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

use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitEvent;
/**
 * Class SummitEventSerializer
 * @package ModelSerializers
 */
class SummitEventSerializer extends SilverStripeSerializer
{

    protected static $array_mappings = [
        'Title'                     => 'title:json_string',
        'Abstract'                  => 'description:json_string',
        'SocialSummary'             => 'social_description:json_string',
        'StartDate'                 => 'start_date:datetime_epoch',
        'EndDate'                   => 'end_date:datetime_epoch',
        'LocationId'                => 'location_id:json_int',
        'SummitId'                  => 'summit_id:json_int',
        'TypeId'                    => 'type_id:json_int',
        'ClassName'                 => 'class_name',
        'AllowFeedBack'             => 'allow_feedback:json_boolean',
        'AvgFeedbackRate'           => 'avg_feedback_rate:json_float',
        'Published'                 => 'is_published:json_boolean',
        'HeadCount'                 => 'head_count:json_int',
        'RSVPLink'                  => 'rsvp_link:json_string',
        'RSVPTemplateId'            => 'rsvp_template_id:json_int',
        'RSVPMaxUserNumber'         => 'rsvp_max_user_number:json_int',
        'RSVPMaxUserWaitListNumber' => 'rsvp_max_user_wait_list_number:json_int',
        'RSVPRegularCount'          => 'rsvp_regular_count:json_int',
        'RSVPWaitCount'             => 'rsvp_wait_count:json_int',
        'ExternalRSVP'              => 'rsvp_external:json_boolean',
        'CategoryId'                => 'track_id:json_int',
        'MeetingUrl'                => 'meeting_url:json_string',
        'TotalAttendanceCount'      => 'attendance_count:json_int',
        'CurrentAttendanceCount'    => 'current_attendance_count:json_int',
        'ImageUrl'                  => 'image:json_url',
        "StreamThumbnailUrl"        => "stream_thumbnail:json_url"
    ];

    protected static $allowed_fields = [
        'id',
        'title',
        'description',
        'social_description',
        'start_date',
        'end_date',
        'location_id',
        'summit_id',
        'type_id',
        'class_name',
        'allow_feedback',
        'avg_feedback_rate',
        'is_published',
        'head_count',
        'rsvp_link',
        'rsvp_external',
        'track_id',
        'rsvp_template_id',
        'rsvp_max_user_number',
        'rsvp_max_user_wait_list_number',
        'rsvp_regular_count',
        'rsvp_wait_count',
        'streaming_url',
        'etherpad_link',
        'meeting_url',
        'attendance_count',
        'current_attendance_count',
        'image',
        'stream_thumbnail',
    ];

    protected static $allowed_relations = [
        'sponsors',
        'tags',
        'feedback',
        'current_attendance',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        $event  = $this->object;
        if(!$event instanceof SummitEvent) return [];

        if(!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('sponsors', $relations))
            $values['sponsors'] = $event->getSponsorsIds();

        if(in_array('tags', $relations))
        {
            $tags = [];
            foreach ($event->getTags() as $tag) {
                $tags[] = $tag->getId();
            }
            $values['tags'] = $tags;
        }

        if(in_array('feedback', $relations))
        {
            $feedback = [];
            $count = 0;
            foreach ($event->getFeedback() as $f) {
                $feedback[] = $f->getId();
                $count++;
                if(AbstractSerializer::MaxCollectionPage < $count) break;
            }
            $values['feedback'] = $feedback;
        }

        if(in_array('current_attendance', $relations)){
            $attendance = [];
            $count = 0;
            foreach ($event->getCurrentAttendance() as $a){
                $attendance[] = $a->getId();
                $count++;
                if(AbstractSerializer::MaxCollectionPage < $count) break;
            }
            $values['current_attendance'] = $attendance;
        }

        if($event->hasAccess($this->resource_server_context->getCurrentUser())){
            $values['streaming_url'] = $event->getStreamingUrl();
            $values['etherpad_link'] = $event->getEtherpadLink();
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'current_attendance':
                    {
                        $attendance = [];
                        $count = 0;
                        foreach ($event->getCurrentAttendance() as $a){
                            $attendance[] =  SerializerRegistry::getInstance()->getSerializer($a)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                            $count++;
                            if(AbstractSerializer::MaxCollectionPage < $count) break;
                        }
                        $values['current_attendance'] = $attendance;
                    }
                    case 'feedback': {
                        $feedback = [];
                        foreach ($event->getFeedback() as $f) {
                            $feedback[] = SerializerRegistry::getInstance()->getSerializer($f)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['feedback'] = $feedback;
                    }
                    break;
                    case 'location': {
                        if($event->hasLocation()){
                            unset($values['location_id']);
                            $values['location'] = SerializerRegistry::getInstance()->getSerializer($event->getLocation())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                    }
                    break;
                    case 'rsvp_template': {
                        if($event->hasRSVPTemplate()){
                            unset($values['rsvp_template_id']);
                            $values['rsvp_template'] = SerializerRegistry::getInstance()->getSerializer($event->getRSVPTemplate())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                    }
                        break;
                    case 'sponsors': {
                        $sponsors = [];
                        foreach ($event->getSponsors() as $s) {
                            $sponsors[] = SerializerRegistry::getInstance()->getSerializer($s)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['sponsors'] = $sponsors;
                    }
                    break;
                    case 'track': {
                       unset($values['track_id']);
                       $values['track'] = SerializerRegistry::getInstance()->getSerializer($event->getCategory())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                    }
                    case 'type': {
                        unset($values['type_id']);
                        $values['type'] = SerializerRegistry::getInstance()->getSerializer($event->getType())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                    }
                    break;
                    case 'tags':{
                        $tags = [];
                        foreach ($event->getTags() as $tag) {
                            $tags[] = SerializerRegistry::getInstance()->getSerializer($tag)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['tags'] = $tags;
                    }
                    break;
                }
            }
        }

        return $values;
    }
}
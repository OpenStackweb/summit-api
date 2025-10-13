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

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\Presentation;
use models\summit\PresentationType;

/**
 * Class PresentationSerializer
 * @package ModelSerializers
 */
class PresentationSerializer extends SummitEventSerializer
{
    const CacheTTL = 1200;

    protected static $array_mappings = [
        'CreatorId'               => 'creator_id:json_int',
        'ModeratorId'             => 'moderator_speaker_id:json_int',
        'SelectionPlanId'         => 'selection_plan_id:json_int',
        'ProblemAddressed'        => 'problem_addressed:json_string',
        'AttendeesExpectedLearnt' => 'attendees_expected_learnt:json_string',
        'ToRecord'                => 'to_record:json_boolean',
        'AttendingMedia'          => 'attending_media:json_boolean',
        'StatusNice'              => 'status:json_string',
        'ProgressNice'            => 'progress:json_string',
        'Slug'                    => 'slug:json_string',
        'SelectionStatus'         => 'selection_status:string',
        'DisclaimerAcceptedDate'  => 'disclaimer_accepted_date:datetime_epoch',
        'DisclaimerAccepted'      => 'disclaimer_accepted:json_boolean',
        'CustomOrder'             => 'custom_order:json_int',
        'AttendeeVotesCount'      => 'attendee_votes_count:json_int',
        'ReviewStatusNice'        => 'review_status:json_string',
    ];

    protected static $allowed_fields = [
        'track_id',
        'creator_id',
        'moderator_speaker_id',
        'selection_plan_id',
        'problem_addressed',
        'attendees_expected_learnt',
        'to_record',
        'attending_media',
        'status',
        'progress',
        'selection_status',
        'disclaimer_accepted_date',
        'disclaimer_accepted',
        'custom_order',
        'attendee_votes_count',
        'review_status',
    ];

    protected static $allowed_relations = [
        'slides',
        'media_uploads',
        'videos',
        'speakers',
        'links',
        'extra_questions',
        'public_comments',
        'actions',
        'creator',
        'selection_plan',
        'moderator',
    ];

    /**
     * @return string
     */
    protected function getMediaUploadsSerializerType():string{
        $serializerType = SerializerRegistry::SerializerType_Public;
        $currentUser = $this->resource_server_context->getCurrentUser();
        $presentation = $this->object;
        if(!is_null($currentUser) && ( $currentUser->isAdmin() || $presentation->memberCanEdit($currentUser))){
            $serializerType = SerializerRegistry::SerializerType_Private;
        }
        return $serializerType;
    }


    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $presentation = $this->object;
        if(!$presentation instanceof Presentation) return [];

        $key =
            sprintf
            (
                "public_presentation_%s_%s_%s_%s",
                $presentation->getId(),
                $expand ?? "",
                implode(",",$fields),
                implode(",", $relations)
            );

        $use_cache = $params['use_cache'] ?? false;

        if($use_cache && Cache::has($key)){
            $values = json_decode(Cache::get($key), true);
            Log::debug(sprintf("PresentationSerializer::serialize cache hit for presentation %s", $presentation->getId()));
            if (!empty($expand)) {
                foreach (explode(',', $expand) as $relation) {
                    $relation = trim($relation);
                    switch ($relation) {
                        case 'media_uploads':
                        {
                            $media_uploads = [];

                            foreach ($presentation->getMediaUploads() as $mediaUpload) {
                                $media_uploads[] = SerializerRegistry::getInstance()->getSerializer
                                (
                                    $mediaUpload, $this->getMediaUploadsSerializerType()
                                )->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                );
                            }

                            $values['media_uploads'] = $media_uploads;
                        }
                    }
                }
            }
            return $values;
        }

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('speakers', $relations)) {
            $values['speakers'] = $presentation->getSpeakerIds();
        }

        if(in_array('slides', $relations))
        {
            $slides = [];
            foreach ($presentation->getSlides() as $slide) {
                $slides[] = $slide->getId();
            }
            $values['slides'] = $slides;
        }

        if(in_array('public_comments', $relations))
        {
            $public_comments = [];
            foreach ($presentation->getPublicComments() as $comment) {
                $public_comments[] = $comment->getId();
            }
            $values['public_comments'] = $public_comments;
        }

        if(in_array('links', $relations))
        {
            $links = [];
            foreach ($presentation->getLinks() as $link) {
                $links[] = $link->getId();
            }
            $values['links'] = $links;
        }

        if(in_array('videos', $relations))
        {
            $videos = [];
            foreach ($presentation->getVideos() as $video) {
                $videos[] = $video->getId();
            }
            $values['videos'] = $videos;
        }

        if(in_array('media_uploads', $relations))
        {
            $media_uploads = [];
            foreach ($presentation->getMediaUploads() as $mediaUpload) {
                $media_uploads[] = $mediaUpload->getId();
            }

            $values['media_uploads'] = $media_uploads;
        }

        if(in_array('extra_questions', $relations))
        {
            $answers = [];
            foreach ($presentation->getExtraQuestionAnswers() as $answer) {
                $answers[] = $answer->getId();
            }
            $values['extra_questions'] = $answers;
        }

        if(in_array('actions', $relations))
        {
            $actions = [];
            foreach ($presentation->getPresentationActions() as $action) {
                $actions[] = $action->getId();
            }
            $values['actions'] = $actions;
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'speakers': {
                        $speakers = [];
                        foreach ($presentation->getSpeakers() as $s) {
                            $serialized_speaker = SerializerRegistry::getInstance()->getSerializer
                            (
                                $s, $this->getSerializerType($relation)
                            )->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                $params
                            );
                            $serialized_speaker['order'] = $s->getPresentationAssignmentOrder($presentation);
                            $speakers[] = $serialized_speaker;
                        }
                        $values['speakers'] = $speakers;
                        if(isset($values['moderator_speaker_id']) && intval($values['moderator_speaker_id']) > 0 ){
                            $values['moderator'] = SerializerRegistry::getInstance()->getSerializer
                            (
                                $presentation->getModerator(),
                                $this->getSerializerType($relation)
                            )->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                $params
                            );
                        }
                    }
                    break;
                    // deprecated
                    case 'creator':
                        {
                            if($presentation->getCreatorId() > 0) {
                                unset($values['creator_id']);
                                $values['creator'] = SerializerRegistry::getInstance()->getSerializer($presentation->getCreator(), $this->getSerializerType($relation))->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                );
                            }
                        }
                        break;
                    case 'selection_plan':{
                        if($presentation->getSelectionPlanId() > 0) {
                            unset($values['selection_plan_id']);
                            $values['selection_plan'] = SerializerRegistry::getInstance()->getSerializer($presentation->getSelectionPlan())->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                    }
                    break;
                    case 'slides':{
                        $slides = [];
                        foreach ($presentation->getSlides() as $slide) {
                            $slide_values  = SerializerRegistry::getInstance()->getSerializer($slide)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                            if(empty($slide_values['link'])) continue;
                            $slides[] = $slide_values;
                        }
                        $values['slides'] = $slides;
                    }
                    break;
                    case 'public_comments':{
                        $public_comments = [];
                        foreach ($presentation->getPublicComments() as $comment) {
                            $public_comments[] = SerializerRegistry::getInstance()->getSerializer($comment)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $values['public_comments'] = $public_comments;
                    }
                    break;
                    case 'links':{
                        $links = [];
                        foreach ($presentation->getLinks() as $link) {
                            $link_values  = SerializerRegistry::getInstance()->getSerializer($link)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                            if(empty($link_values['link'])) continue;
                            $links[] = $link_values;
                        }
                        $values['links'] = $links;
                    }
                    break;
                    case 'videos':{
                        $videos = [];
                        foreach ($presentation->getVideos() as $video) {
                            $video_values   = SerializerRegistry::getInstance()->getSerializer($video)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                            $videos[] = $video_values;
                        }
                        $values['videos'] = $videos;
                    }
                    break;
                    case 'media_uploads':{
                        $media_uploads = [];

                        foreach ($presentation->getMediaUploads() as $mediaUpload) {
                            $media_uploads[] = SerializerRegistry::getInstance()->getSerializer
                            (
                                $mediaUpload, $this->getMediaUploadsSerializerType()
                            )->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }

                        $values['media_uploads'] = $media_uploads;
                    }
                    break;
                    case 'extra_questions':{
                        $answers = [];
                        foreach ($presentation->getExtraQuestionAnswers() as $answer) {
                            $answers[]= SerializerRegistry::getInstance()->getSerializer($answer)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $values['extra_questions'] = $answers;
                    }
                    break;
                    case 'actions':{
                        $actions = [];
                        foreach ($presentation->getPresentationActions() as $action) {
                            $actions[]= SerializerRegistry::getInstance()->getSerializer($action)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }
                        $values['actions'] = $actions;
                    }
                    break;
                    case 'moderator':{
                        $type = $presentation->getType();
                        if($type instanceof PresentationType && $type->isUseModerator() && $presentation->hasModerator())
                        {
                            unset($values['moderator_speaker_id']);
                            $values['moderator'] = SerializerRegistry::getInstance()->getSerializer
                            (
                                $presentation->getModerator(),
                                $this->getSerializerType($relation)
                            )->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                $params
                            );
                        }
                    }
                    break;
                }
            }
        }

        if($use_cache)
            Cache::put($key, json_encode($values), self::CacheTTL);

        return $values;
    }
}

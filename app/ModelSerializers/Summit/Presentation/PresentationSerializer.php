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

use App\Security\SummitScopes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use models\oauth2\IResourceServerContext;
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
     * Resolves who is allowed to see every media upload attached to this presentation, approved
     * or not. Kept aligned with OAuth2SummitEventsApiController::getSerializerType(), which is
     * what decides the serializer type of the presentation itself - when the two disagree the
     * presentation is served Private while its uploads are served Public, which is the bug this
     * method used to have for summit admins and for service accounts.
     *
     * Two distinct privileged callers:
     *
     * - Service accounts (client_credentials, so getCurrentUser() is null by construction) that
     *   hold the dedicated snapshot scope. The content pipeline stages files pre-event, so it
     *   needs unapproved uploads. Gated on the scope and not on ApplicationType_Service alone:
     *   the application type on its own would hand drafts to every service client.
     * - Members with an admin-level group, or with edit rights over this presentation
     *   (creator / moderator / speaker).
     *
     * @return string
     */
    protected function getMediaUploadsSerializerType():string{
        // && short-circuits, so getCurrentScope() is only reached for service accounts
        $isSnapshotClient =
            $this->resource_server_context->getApplicationType() === IResourceServerContext::ApplicationType_Service
            && in_array
            (
                SummitScopes::ReadAllPresentationMediaUploads,
                $this->resource_server_context->getCurrentScope()
            );

        if ($isSnapshotClient)
            return SerializerRegistry::SerializerType_Private;

        $serializerType = SerializerRegistry::SerializerType_Public;
        $currentUser = $this->resource_server_context->getCurrentUser();
        $presentation = $this->object;
        if(!is_null($currentUser) && ( $currentUser->isAdmin() || $currentUser->isSummitAdmin() || $presentation->memberCanEdit($currentUser))){
            $serializerType = SerializerRegistry::SerializerType_Private;
        }
        return $serializerType;
    }

    /**
     * Media uploads visible to the resolved serializer type. A Public caller (no admin/editor
     * privilege on this presentation) only ever sees uploads marked display_on_site=true — an
     * uploaded-but-not-yet-approved draft (display_on_site=false, the model's own default) must
     * never reach an unauthenticated/public response, even via ?expand=media_uploads on the
     * public events/published endpoints.
     * @return \Doctrine\Common\Collections\Collection|PresentationMediaUpload[]
     */
    protected function getVisibleMediaUploads()
    {
        $presentation = $this->object;
        $mediaUploads = $presentation->getMediaUploads();
        if ($this->getMediaUploadsSerializerType() === SerializerRegistry::SerializerType_Private) {
            return $mediaUploads;
        }
        return $mediaUploads->filter(function ($mediaUpload) {
            return $mediaUpload->getDisplayOnSite();
        });
    }

    /**
     * Sets media_uploads on an already-built payload for whoever is asking right now, in the
     * shape the request asked for: an id list for ?relations=media_uploads, serialized objects
     * for ?expand=media_uploads, expand winning when both are present.
     *
     * This is the only place that decides the value, and it runs on every path - including
     * after a cache read - because getMediaUploadsSerializerType() resolves per user and per
     * scope, which nothing in the cache key expresses. Two callers can share a key and still
     * disagree here: a speaker on the presentation and a plain attendee both serialize through
     * PresentationSerializer, and a service account holding ReadAllPresentationMediaUploads and
     * one without it both serialize through AdminPresentationSerializer. Adding the serializer
     * class to the key would not separate either pair.
     *
     * @param array $values
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @return array
     */
    private function withMediaUploads(array $values, $expand, array $fields, array $relations): array
    {
        // Nothing asked for it: drop whatever a cached payload may be carrying, so a stale
        // entry can never contribute this field to a response that did not request it.
        unset($values['media_uploads']);

        if (in_array('media_uploads', $relations)) {
            $media_uploads = [];
            foreach ($this->getVisibleMediaUploads() as $mediaUpload) {
                $media_uploads[] = $mediaUpload->getId();
            }
            $values['media_uploads'] = $media_uploads;
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                if (trim($relation) !== 'media_uploads') continue;

                $media_uploads = [];
                foreach ($this->getVisibleMediaUploads() as $mediaUpload) {
                    $media_uploads[] = SerializerRegistry::getInstance()->getSerializer
                    (
                        $mediaUpload, $this->getMediaUploadsSerializerType()
                    )->serialize
                    (
                        AbstractSerializer::filterExpandByPrefix($expand, 'media_uploads'),
                        AbstractSerializer::filterFieldsByPrefix($fields, 'media_uploads'),
                        AbstractSerializer::filterFieldsByPrefix($relations, 'media_uploads'),
                    );
                }
                $values['media_uploads'] = $media_uploads;
            }
        }

        return $values;
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

        // Include last_edited timestamp so a presentation update naturally busts the cache
        // without needing an explicit Cache::forget — the old key just ages out via TTL.
        $key =
            sprintf
            (
                "public_presentation_%s_%s_%s_%s_%s",
                $presentation->getId(),
                $presentation->getLastEditedUTC()?->getTimestamp() ?? 0,
                $expand ?? "",
                implode(",",$fields),
                implode(",", $relations)
            );

        $use_cache = $params['use_cache'] ?? false;

        if($use_cache && Cache::has($key)){
            $values = json_decode(Cache::get($key), true);
            Log::debug(sprintf("PresentationSerializer::serialize cache hit for presentation %s", $presentation->getId()));
            return $this->withMediaUploads($values, $expand, $fields, $relations);
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

        if($use_cache) {
            // media_uploads is deliberately kept out of the stored payload: it is the one field
            // here whose value depends on who is asking, and the key has no audience component.
            // Storing it would make correctness depend on every future reader remembering to
            // recompute it.
            $cacheable = $values;
            unset($cacheable['media_uploads']);
            Cache::put($key, json_encode($cacheable), self::CacheTTL);
        }

        return $this->withMediaUploads($values, $expand, $fields, $relations);
    }
}

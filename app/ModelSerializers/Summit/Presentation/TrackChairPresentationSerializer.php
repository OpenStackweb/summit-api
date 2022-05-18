<?php namespace ModelSerializers;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\summit\Presentation;
/**
 * Class TrackChairPresentationSerializer
 * @package ModelSerializers
 */
class TrackChairPresentationSerializer extends AdminPresentationSerializer
{

    protected static $array_mappings = [
        'GroupSelected' => 'is_group_selected:json_bool',
        'ViewsCount'  => 'views_count:json_int',
        'CommentsCount' => 'comments_count:json_int',
        'PopularityScore' => 'popularity_score:json_float',
        'VotesCount'      => 'votes_count:json_int',
        'VotesAverage' => 'votes_average:json_float',
        'VotesTotalPoints' => 'votes_total_points:json_int',
        'TrackChairAvgScore' => 'track_chair_avg_score:json_float',
     ];

    protected static $allowed_fields = [
        'is_group_selected',
        'views_count',
        'comments_count',
        'popularity_score',
        'votes_count',
        'votes_average',
        'votes_total_points',
        'track_chair_avg_score',
        'remaining_selections',
    ];

    protected static $allowed_relations = [
        'slides',
        'media_uploads',
        'videos',
        'speakers',
        'links',
        'extra_questions',
        'public_comments',
        'selectors',
        'likers',
        'passers',
        'comments',
        'viewers',
        'category_changes_requests',
        'track_chair_scores',
    ];

    /**
     * @return string
     */
    protected function getMediaUploadsSerializerType():string{
        return SerializerRegistry::SerializerType_Private;
    }

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        if(!count($relations)) $relations = $this->getAllowedRelations();

        $presentation = $this->object;

        if(!$presentation instanceof Presentation) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        $member = $this->resource_server_context->getCurrentUser(false);

        $values['remaining_selections'] = $presentation->getRemainingSelectionsForMember($member);

        $summit_track_chair = $presentation->getSummit()->getTrackChairByMember($member);

        if(in_array('selectors', $relations))
        {
            $selectors = [];
            foreach ($presentation->getSelectors() as $m) {
                $selectors[] = $m->getId();
            }
            $values['selectors'] = $selectors;
        }

        if(in_array('likers', $relations))
        {
            $likers = [];
            foreach ($presentation->getLikers() as $m) {
                $likers[] = $m->getId();
            }
            $values['likers'] = $likers;
        }

        if(in_array('viewers', $relations))
        {
            $viewers = [];
            foreach ($presentation->getMemberViewers() as $m) {
                $viewers[] = $m->getId();
            }
            $values['viewers'] = $viewers;
        }

        if(in_array('passers', $relations))
        {
            $passers = [];
            foreach ($presentation->getPassers() as $m) {
                $passers[] = $m->getId();
            }
            $values['passers'] = $passers;
        }

        if(in_array('comments', $relations))
        {
            $comments = [];
            foreach ($presentation->getComments() as $comment) {
                $comments[] = $comment->getId();
            }
            $values['comments'] = $comments;
        }

        if(in_array('category_changes_requests', $relations))
        {
            $category_changes_requests = [];
            foreach ($presentation->getCategoryChangeRequests() as $request) {
                $category_changes_requests[] = $request->getId();
            }
            $values['category_changes_requests'] = $category_changes_requests;
        }

        if(in_array('track_chair_scores', $relations)){
            $track_chair_scores = [];
            foreach ($presentation->getTrackChairScoresBy($summit_track_chair) as $score) {
                $track_chair_scores[] = $score->getId();
            }
            $values['track_chair_scores'] = $track_chair_scores;
        };

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'selectors': {
                        $selectors = [];
                        foreach ($presentation->getSelectors() as $m) {
                            $selectors[] = SerializerRegistry::getInstance()->getSerializer($m)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['selectors'] = $selectors;
                    }
                        break;
                    case 'likers': {
                        $likers = [];
                        foreach ($presentation->getLikers() as $m) {
                            $likers[] = SerializerRegistry::getInstance()->getSerializer($m)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['likers'] = $likers;
                    }
                        break;
                    case 'passers': {
                        $passers = [];
                        foreach ($presentation->getPassers() as $m) {
                            $passers[] = SerializerRegistry::getInstance()->getSerializer($m)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['passers'] = $passers;
                    }
                        break;
                    case 'viewers': {
                        $viewers = [];
                        foreach ($presentation->getMemberViewers() as $m) {
                            $viewers[] = SerializerRegistry::getInstance()->getSerializer($m)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['viewers'] = $viewers;
                    }
                        break;
                    case 'comments':{
                        $comments = [];
                        foreach ($presentation->getComments() as $comment) {
                            $comments[] = SerializerRegistry::getInstance()->getSerializer($comment)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['comments'] = $comments;
                    }
                        break;
                    case 'category_changes_requests':{
                        $category_changes_requests = [];
                        foreach ($presentation->getCategoryChangeRequests() as $request) {
                            $category_changes_requests[] = SerializerRegistry::getInstance()->getSerializer($request)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['category_changes_requests'] = $category_changes_requests;
                    }
                        break;
                    case 'track_chair_scores':{
                        $track_chair_scores = [];
                        foreach ($presentation->getTrackChairScoresBy($summit_track_chair) as $score) {
                            $track_chair_scores[] = SerializerRegistry::getInstance()->getSerializer($score)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['track_chair_scores'] = $track_chair_scores;
                    }
                        break;
                }
            }
        }
        return $values;
    }
}
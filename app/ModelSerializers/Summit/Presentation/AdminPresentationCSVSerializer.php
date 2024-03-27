<?php namespace ModelSerializers;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\ModelSerializers\Traits\RequestCache;
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use models\main\Member;
use models\summit\Presentation;
/**
 * Class AdminPresentationCSVSerializer
 * @package ModelSerializers
 */
final class AdminPresentationCSVSerializer extends AdminPresentationSerializer
{
    protected static $allowed_fields = [
        'moderator_id',
        'moderator_full_name',
        'moderator_email',
        'moderator_title',
        'moderator_company',
        'speaker_ids',
        'speaker_fullnames',
        'speaker_emails',
        'speaker_titles',
        'speaker_companies',
        'speaker_countries',
        'submitter_id',
        'submitter_full_name',
        'submitter_email',
        'submitter_title',
        'submitter_company',
        'submitter_country',
        'video',
        'public_video',
        'extra_questions',
        'presentation_flags',
        'created_by',
        'location_name',
    ];

    use RequestCache;

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [] )
    {
        $scope = sprintf("presentation_cache_scope_%s", $this->object->getIdentifier());
        $cache_key = sprintf("%s_%s", $scope, implode("_", $expand));
        return $this->cache
        (
            $scope,
            $cache_key,
            function () use ($expand, $fields, $relations, $params) {
                if(!count($fields)) $fields = $this->getAllowedFields();

                $values = parent::serialize($expand, $fields, $relations, $params);
                $presentation = $this->object;
                if(!$presentation instanceof Presentation) return $values;

                $serializerType = SerializerRegistry::SerializerType_Public;

                if(isset($params['current_user']) && $params['current_user'] instanceof Member && $params['current_user']->isAdmin()){
                    $serializerType = SerializerRegistry::SerializerType_Private;
                }

                // moderator data
                if(in_array("moderator_id",$fields))
                    $values['moderator_id'] = "";
                if(in_array("moderator_full_name",$fields))
                    $values['moderator_full_name'] = "";
                if(in_array("moderator_email",$fields))
                    $values['moderator_email'] = "";
                if(in_array("moderator_title",$fields))
                    $values['moderator_title'] = "";
                if(in_array("moderator_company",$fields))
                    $values['moderator_company'] = "";

                if(isset($values['moderator_speaker_id']))
                    unset($values['moderator_speaker_id']);

                if($presentation->hasModerator()){
                    if(in_array("moderator_id",$fields))
                        $values['moderator_id'] = $presentation->getModerator()->getId();
                    if(in_array("moderator_full_name",$fields))
                        $values['moderator_full_name'] = $presentation->getModerator()->getFullName();
                    if(in_array("moderator_email",$fields))
                        $values['moderator_email'] = $presentation->getModerator()->getEmail();
                    if(in_array("moderator_title",$fields))
                        $values['moderator_title'] = trim($presentation->getModerator()->getTitle());
                    if(in_array("moderator_company",$fields))
                        $values['moderator_company'] = trim($presentation->getModerator()->getCompany());
                }

                // speaker data
                if(in_array("speaker_ids",$fields))
                    $values['speaker_ids'] = "";
                if(in_array("speaker_fullnames",$fields))
                    $values['speaker_fullnames'] = "";
                if(in_array("speaker_emails",$fields))
                    $values['speaker_emails'] = "";
                if(in_array("speaker_titles",$fields))
                    $values['speaker_titles'] = "";
                if(in_array("speaker_companies",$fields))
                    $values['speaker_companies'] = "";
                if(in_array("speaker_countries",$fields))
                    $values['speaker_countries'] = "";

                if($presentation->getSpeakers()->count() > 0){

                    $speaker_ids = [];
                    $speaker_fullnames = [];
                    $speaker_emails = [];
                    $speaker_titles = [];
                    $speaker_companies = [];
                    $speaker_countries = [];

                    foreach ($presentation->getSpeakers() as $speaker) {
                        $speaker_ids[] = $speaker->getId();
                        $speaker_fullnames[] = $speaker->getFullName();
                        $speaker_emails[] = $speaker->getEmail();
                        $speaker_titles[] = trim($speaker->getTitle());
                        $speaker_companies[] = trim($speaker->getCompany());
                        $speaker_countries[] = trim($speaker->getCountry());
                    }

                    if(in_array("speaker_ids",$fields))
                        $values['speaker_ids'] = implode("|", $speaker_ids);
                    if(in_array("speaker_fullnames",$fields))
                        $values['speaker_fullnames'] = implode("|", $speaker_fullnames);
                    if(in_array("speaker_emails",$fields))
                        $values['speaker_emails'] = implode("|", $speaker_emails);
                    if(in_array("speaker_titles",$fields))
                        $values['speaker_titles'] = implode("|", $speaker_titles);
                    if(in_array("speaker_companies",$fields))
                        $values['speaker_companies'] = implode("|", $speaker_companies);
                    if(in_array("speaker_countries",$fields))
                        $values['speaker_countries'] = implode("|", $speaker_countries);
                }

                // submitter
                if(in_array("submitter_id",$fields))
                    $values['submitter_id'] = "";
                if(in_array("submitter_full_name",$fields))
                    $values['submitter_full_name'] = "";
                if(in_array("submitter_email",$fields))
                    $values['submitter_email'] = "";
                if(in_array("submitter_title",$fields))
                    $values['submitter_title'] = "";
                if(in_array("submitter_company",$fields))
                    $values['submitter_company'] = "";
                if(in_array("submitter_country",$fields))
                    $values['submitter_country'] = "";

                if($presentation->hasCreatedBy()){
                    $creator = $presentation->getCreatedBy();
                    if($creator->hasSpeaker()){
                        $submitter = $creator->getSpeaker();
                        if(in_array("submitter_id",$fields))
                            $values['submitter_id'] = $submitter->getId();
                        if(in_array("submitter_full_name",$fields))
                            $values['submitter_full_name'] = $submitter->getFullName();
                        if(in_array("submitter_email",$fields))
                            $values['submitter_email'] = $submitter->getEmail();
                        if(in_array("submitter_title",$fields))
                            $values['submitter_title'] = $submitter->getTitle();
                        if(in_array("submitter_company",$fields))
                            $values['submitter_company'] = $submitter->getCompany();
                        if(in_array("submitter_country",$fields))
                            $values['submitter_country'] = $submitter->getCountry();
                    }
                }

                if(isset($values['description'])){
                    $values['description'] = strip_tags($values['description']);
                }
                if(isset($values['attendees_expected_learnt'])){
                    $values['attendees_expected_learnt'] = strip_tags($values['attendees_expected_learnt']);
                }

                // add video column
                if(in_array("video",$fields))
                    $values['video'] = '';
                if(in_array("public_video",$fields))
                    $values['public_video'] = '';

                foreach ($presentation->getMediaUploads() as $mediaUpload) {
                    if($mediaUpload->getMediaUploadType()->isVideo()) {
                        $media_upload_csv = SerializerRegistry::getInstance()->getSerializer($mediaUpload, $serializerType)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'media_uploads'));;
                        if(!isset($media_upload_csv['private_url']) || !isset($media_upload_csv['filename'])){
                            Log::warning(sprintf("AdminPresentationCSVSerializer::serialize can not process media upload %s", json_encode($media_upload_csv)));
                            continue;
                        }
                        if(in_array("video",$fields))
                            $values['video'] = sprintf('=HYPERLINK("%s";"%s")', $media_upload_csv['private_url'], $media_upload_csv['filename']);

                        if(!isset($media_upload_csv['public_url']) || !isset($media_upload_csv['filename'])){
                            Log::warning(sprintf("AdminPresentationCSVSerializer::serialize can not process media upload %s", json_encode($media_upload_csv)));
                            continue;
                        }
                        if(in_array("public_video",$fields))
                            $values['public_video'] = sprintf('=HYPERLINK("%s";"%s")', $media_upload_csv['public_url'], $media_upload_csv['filename']);
                    }
                }

                // extra questions
                if(in_array("extra_questions",$fields)) {
                    $values['extra_questions'] = '';
                    foreach ($presentation->getExtraQuestionAnswers() as $answer) {
                        if (!empty($values['extra_questions']))
                            $values['extra_questions'] = $values['extra_questions'] . '|';
                        $values['extra_questions'] = $values['extra_questions'] . str_replace(",", "", (string)$answer);
                    }
                }

                if(in_array("track", $fields) && $presentation->hasCategory()){
                    $values['track'] = $presentation->getCategory()->getTitle();
                }

                if(in_array("presentation_flags",$fields)) {
                    // presentation flags
                    $values['presentation_flags'] = '';
                    foreach ($presentation->getPresentationActions() as $action) {
                        if (!empty($values['presentation_flags']))
                            $values['presentation_flags'] = $values['presentation_flags'] . '|';
                        $values['presentation_flags'] = $values['presentation_flags'] . str_replace(",", "", (string)$action);
                    }
                }

                if(in_array("created_by",$fields)) {
                    $values['created_by'] = '';
                    if ($presentation->hasCreatedBy()) {
                        unset($values['created_by_id']);
                        $created_by = $presentation->getCreatedBy();
                        $values['created_by'] = sprintf("%s (%s)", $created_by->getFullName(), $created_by->getEmail());
                    }
                }

                if(in_array("location_name",$fields) && $presentation->hasLocation()){
                    $values['location_name'] = $presentation->getLocation()->getName();
                }
                if(isset($values['status'])) {
                    $values['submission_status'] = $values['status'];
                    unset($values['status']);
                }
                return $values;
            }
        );

    }
}
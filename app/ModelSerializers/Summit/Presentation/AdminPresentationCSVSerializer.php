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
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use libs\utils\JsonUtils;
use models\main\Member;
use models\summit\Presentation;
/**
 * Class AdminPresentationCSVSerializer
 * @package ModelSerializers
 */
final class AdminPresentationCSVSerializer extends AdminPresentationSerializer
{
    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [] )
    {
        $values = parent::serialize($expand, $fields, $relations, $params);
        $presentation = $this->object;
        if(!$presentation instanceof Presentation) return $values;

        $serializerType = SerializerRegistry::SerializerType_Public;

        if(isset($params['current_user']) && $params['current_user'] instanceof Member && $params['current_user']->isAdmin()){
            $serializerType = SerializerRegistry::SerializerType_Private;
        }

        // local dates
        $localStartDate = $presentation->getLocalStartDate();
        if(!is_null($localStartDate))
            $values['start_date'] = $localStartDate->getTimestamp();

        $localEndDate = $presentation->getLocalEndDate();
        if(!is_null($localEndDate))
            $values['end_date'] = $localEndDate->getTimestamp();

        // moderator data

        $values['moderator_id'] = "";
        $values['moderator_full_name'] = "";
        $values['moderator_email'] = "";
        $values['moderator_title'] = "";
        $values['moderator_company'] = "";

        if(isset($values['moderator_speaker_id']))
            unset($values['moderator_speaker_id']);

        if($presentation->hasModerator()){
            $values['moderator_id'] = $presentation->getModerator()->getId();
            $values['moderator_full_name'] = $presentation->getModerator()->getFullName();
            $values['moderator_email'] = $presentation->getModerator()->getEmail();
            $values['moderator_title'] = trim($presentation->getModerator()->getTitle());
            $values['moderator_company'] = trim($presentation->getModerator()->getCompany());
        }

        // speaker data

        $values['speaker_ids'] = "";
        $values['speaker_fullnames'] = "";
        $values['speaker_emails'] = "";
        $values['speaker_titles'] = "";
        $values['speaker_companies'] = "";

        if($presentation->getSpeakers()->count() > 0){

            $speaker_ids = [];
            $speaker_fullnames = [];
            $speaker_emails = [];
            $speaker_titles = [];
            $speaker_companies = [];

            foreach ($presentation->getSpeakers() as $speaker) {
                $speaker_ids[] = $speaker->getId();
                $speaker_fullnames[] = $speaker->getFullName();
                $speaker_emails[] = $speaker->getEmail();
                $speaker_titles[] = trim($speaker->getTitle());
                $speaker_companies[] = trim($speaker->getCompany());
            }

            $values['speaker_ids'] = implode("|", $speaker_ids);
            $values['speaker_fullnames'] = implode("|", $speaker_fullnames);
            $values['speaker_emails'] = implode("|", $speaker_emails);
            $values['speaker_titles'] = implode("|", $speaker_titles);
            $values['speaker_companies'] = implode("|", $speaker_companies);
        }

        // submitter

        $values['submitter_id'] = "";
        $values['submitter_full_name'] = "";
        $values['submitter_email'] = "";
        $values['submitter_title'] = "";
        $values['submitter_company'] = "";

        if($presentation->hasCreatedBy()){
            $creator = $presentation->getCreatedBy();
            if($creator->hasSpeaker()){
                $submitter = $creator->getSpeaker();
                $values['submitter_id'] = $submitter->getId();
                $values['submitter_full_name'] = $submitter->getFullName();
                $values['submitter_email'] = $submitter->getEmail();
                $values['submitter_title'] = $submitter->getTitle();
                $values['submitter_company'] = $submitter->getCompany();
            }
        }

        if(isset($values['description'])){
            $values['description'] = strip_tags($values['description']);
        }
        if(isset($values['attendees_expected_learnt'])){
            $values['attendees_expected_learnt'] = strip_tags($values['attendees_expected_learnt']);
        }

        // add video column

        $values['video'] = '';
        $values['public_video'] = '';

        foreach ($presentation->getMediaUploads() as $mediaUpload) {
            if($mediaUpload->getMediaUploadType()->isVideo()) {
                $media_upload_csv = SerializerRegistry::getInstance()->getSerializer($mediaUpload, $serializerType)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'media_uploads'));;
                if(!isset($media_upload_csv['private_url']) || !isset($media_upload_csv['filename'])){
                    Log::warning(sprintf("AdminPresentationCSVSerializer::serialize can not process media upload %s", json_encode($media_upload_csv)));
                    continue;
                }
                $values['video'] = sprintf('=HYPERLINK("%s";"%s")', $media_upload_csv['private_url'], $media_upload_csv['filename']);

                if(!isset($media_upload_csv['public_url']) || !isset($media_upload_csv['filename'])){
                    Log::warning(sprintf("AdminPresentationCSVSerializer::serialize can not process media upload %s", json_encode($media_upload_csv)));
                    continue;
                }
                $values['public_video'] = sprintf('=HYPERLINK("%s";"%s")', $media_upload_csv['public_url'], $media_upload_csv['filename']);
            }
        }

        // extra questions

        $values['extra_questions'] = '';
        foreach ($presentation->getExtraQuestionAnswers() as $answer){
            if(!empty($values['extra_questions']))
                $values['extra_questions'] = $values['extra_questions'] . '|';
            $values['extra_questions'] =  $values['extra_questions'] . str_replace(",", "", (string)$answer);
        }

        if($presentation->hasCategory()){
            $values['track'] = $presentation->getCategory()->getTitle();
        }


        // presentation flags
        $values['presentation_flags'] = '';
        foreach($presentation->getPresentationActions() as $action){
            if(!empty($values['presentation_flags']))
                $values['presentation_flags'] = $values['presentation_flags'] . '|';
            $values['presentation_flags'] =  $values['presentation_flags'] . str_replace(",", "", (string)$action);
        }
        return $values;
    }
}
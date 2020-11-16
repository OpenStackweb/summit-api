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
        $currentUser = $this->resource_server_context->getCurrentUser();
        if(!is_null($currentUser) && $currentUser->isAdmin()){
            $serializerType = SerializerRegistry::SerializerType_Private;
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

        return $values;
    }
}
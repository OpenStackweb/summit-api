<?php namespace App\Services\Model;
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
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\PresentationType;
use models\summit\Summit;
use models\summit\SummitMediaUploadType;
/**
 * Interface ISummitMediaUploadTypeService
 * @package App\Services\Model
 */
interface ISummitMediaUploadTypeService
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitMediaUploadType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function add(Summit $summit, array $data):SummitMediaUploadType;

    /**
     * @param Summit $summit
     * @param int $media_upload_type_id
     * @param array $data
     * @return SummitMediaUploadType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function update(Summit $summit, int $media_upload_type_id, array $data):SummitMediaUploadType;

    /**
     * @param Summit $summit
     * @param int $media_upload_type_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function delete(Summit $summit, int $media_upload_type_id):void;

    /**
     * @param Summit $summit
     * @param int $media_upload_type_id
     * @param int $presentation_type_id
     * @return SummitMediaUploadType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addToPresentationType(Summit $summit, int $media_upload_type_id, int $presentation_type_id):SummitMediaUploadType;

    /**
     * @param Summit $summit
     * @param int $media_upload_type_id
     * @param int $presentation_type_id
     * @return SummitMediaUploadType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteFromPresentationType(Summit $summit, int $media_upload_type_id,  int $presentation_type_id):SummitMediaUploadType;

    /**
     * @param Summit $fromSummit
     * @param Summit $toSummit
     * @return Summit
     */
    public function cloneMediaUploadTypes(Summit $fromSummit, Summit $toSummit):Summit;
}
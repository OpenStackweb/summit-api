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
use models\summit\Summit;
use models\summit\SummitDocument;
/**
 * Interface ISummitDocumentService
 * @package App\Services\Model
 */
interface ISummitDocumentService
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @throws EntityNotFoundException
     * @throws ValidationException

     * @return SummitDocument
     */
    public function addSummitDocument(Summit $summit, array $payload):SummitDocument;

    /**
     * @param Summit $summit
     * @param int $document_id
     * @param array $payload
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return SummitDocument
     */
    public function updateSummitDocument(Summit $summit, int $document_id, array $payload):SummitDocument;

    /**
     * @param Summit $summit
     * @param int $document_id
     * @throws EntityNotFoundException
     */
    public function deleteSummitDocument(Summit $summit, int $document_id):void;

    /**
     * @param Summit $summit
     * @param int $document_id
     * @param int $event_type_id
     * @return SummitDocument
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addEventTypeToSummitDocument(Summit $summit, int $document_id, int $event_type_id):SummitDocument;

    /**
     * @param Summit $summit
     * @param int $document_id
     * @param int $event_type_id
     * @return SummitDocument
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeEventTypeFromSummitDocument(Summit $summit, int $document_id, int $event_type_id):SummitDocument;
}
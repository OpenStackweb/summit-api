<?php namespace App\Http\Controllers;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeNoteRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\IAttendeeService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;

/**
 * Class OAuth2SummitAttendeeNotesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitAttendeeNotesApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    use ParametrizedGetAll;

    /**
     * @var IAttendeeService
     */
    private $attendee_service;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * OAuth2SummitAttendeeNotesApiController constructor.
     * @param ISummitAttendeeNoteRepository $attendee_notes_repository
     * @param ISummitAttendeeRepository $attendee_repository
     * @param ISummitRepository $summit_repository
     * @param IAttendeeService $attendee_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitAttendeeNoteRepository $attendee_notes_repository,
        ISummitAttendeeRepository     $attendee_repository,
        ISummitRepository             $summit_repository,
        IAttendeeService              $attendee_service,
        IResourceServerContext        $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->attendee_repository = $attendee_repository;
        $this->summit_repository = $summit_repository;
        $this->repository = $attendee_notes_repository;
        $this->attendee_service = $attendee_service;
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllAttendeeNotes($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'owner_id' => ['=='],
                    'owner_fullname' => ['=@', '==', '@@'],
                    'owner_email' => ['=@', '==', '@@'],
                    'ticket_id' => ['=='],
                    'content' => ['=@', '@@'],
                    'author_fullname' => ['=@', '==', '@@'],
                    'author_email' => ['=@', '==', '@@'],
                    'created' => ['==', '>=', '<=', '>', '<'],
                    'edited' => ['==', '>=', '<=', '>', '<'],
                ];
            },
            function () {
                return [
                    'owner_id' => 'sometimes|integer',
                    'owner_fullname' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'ticket_id' => 'sometimes|integer',
                    'content' => 'sometimes|string',
                    'author_fullname' => 'sometimes|string',
                    'author_email' => 'sometimes|string',
                    'created' => 'sometimes|required|date_format:U',
                    'edited' => 'sometimes|required|date_format:U',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'author_fullname',
                    'author_email',
                    'owner_id',
                    'owner_fullname',
                    'owner_email',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            }
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllAttendeeNotesCSV($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'owner_id' => ['=='],
                    'owner_fullname' => ['=@', '==', '@@'],
                    'owner_email' => ['=@', '==', '@@'],
                    'ticket_id' => ['=='],
                    'content' => ['=@', '@@'],
                    'author_fullname' => ['=@', '==', '@@'],
                    'author_email' => ['=@', '==', '@@'],
                    'created' => ['==', '>=', '<=', '>', '<'],
                    'edited' => ['==', '>=', '<=', '>', '<'],
                ];
            },
            function () {
                return [
                    'owner_id' => 'sometimes|integer',
                    'owner_fullname' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'ticket_id' => 'sometimes|integer',
                    'content' => 'sometimes|string',
                    'author_fullname' => 'sometimes|string',
                    'author_email' => 'sometimes|string',
                    'created' => 'sometimes|required|date_format:U',
                    'edited' => 'sometimes|required|date_format:U',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'author_fullname',
                    'author_email',
                    'owner_id',
                    'owner_fullname',
                    'owner_email',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_CSV;
            },
            function () {
                return [
                    'created' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                ];
            },
            function () {
                return [];
            },
            'attendees-notes-'
        );
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @return mixed
     */
    public function getAttendeeNotes($summit_id, $attendee_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $attendee = $this->attendee_repository->getById(intval($attendee_id));
        if (is_null($attendee)) return $this->error404();

        if ($attendee->getSummit()->getId() != intval($summit_id))
            return $this->error412("Attendee id {$attendee_id} does not belong to summit {$summit_id}.");

        return $this->_getAll(
            function () {
                return [
                    'ticket_id' => ['=='],
                    'content' => ['=@', '@@'],
                    'author_fullname' => ['=@', '==', '@@'],
                    'author_email' => ['=@', '==', '@@'],
                    'created' => ['==', '>=', '<=', '>', '<'],
                    'edited' => ['==', '>=', '<=', '>', '<'],
                ];
            },
            function () {
                return [
                    'ticket_id' => 'sometimes|integer',
                    'content' => 'sometimes|string',
                    'author_fullname' => 'sometimes|string',
                    'author_email' => 'sometimes|string',
                    'created' => 'sometimes|required|date_format:U',
                    'edited' => 'sometimes|required|date_format:U',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'author_fullname',
                    'author_email',
                ];
            },
            function ($filter) use ($summit, $attendee) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('owner_id', $attendee->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            }
        );
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @return mixed
     */
    public function getAttendeeNotesCSV($summit_id, $attendee_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $attendee = $this->attendee_repository->getById(intval($attendee_id));
        if (is_null($attendee)) return $this->error404();

        if ($attendee->getSummit()->getId() != intval($summit_id))
            return $this->error412("Attendee id {$attendee_id} does not belong to summit {$summit_id}.");

        return $this->_getAllCSV(
            function () {
                return [
                    'ticket_id' => ['=='],
                    'content' => ['=@', '@@'],
                    'author_fullname' => ['=@', '==', '@@'],
                    'author_email' => ['=@', '==', '@@'],
                    'created' => ['==', '>=', '<=', '>', '<'],
                    'edited' => ['==', '>=', '<=', '>', '<'],
                ];
            },
            function () {
                return [
                    'ticket_id' => 'sometimes|integer',
                    'content' => 'sometimes|string',
                    'author_fullname' => 'sometimes|string',
                    'author_email' => 'sometimes|string',
                    'created' => 'sometimes|required|date_format:U',
                    'edited' => 'sometimes|required|date_format:U',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'author_fullname',
                    'author_email',
                ];
            },
            function ($filter) use ($summit, $attendee) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('owner_id', $attendee->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_CSV;
            },
            function () {
                return [
                    'created' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                ];
            },
            function () {
                return [];
            },
            sprintf('attendee-%s-notes-', $attendee_id)
        );
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @param $note_id
     * @return mixed
     */
    public function getAttendeeNote($summit_id, $attendee_id, $note_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id, $note_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee = $summit->getAttendeeById(intval($attendee_id));
            if (is_null($attendee)) return $this->error404("Attendee id {$attendee_id} not found in summit {$summit_id}.");

            $attendee_note = $attendee->getNoteById(intval($note_id));
            if (is_null($attendee_note)) return $this->error404("Attendee note id {$note_id} not found for attendee {$attendee_id}.");

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer
                (
                    $attendee_note,
                    SerializerRegistry::SerializerType_Private
                )->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    ['serializer_type' => SerializerRegistry::SerializerType_Private]
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @return mixed
     */
    public function addAttendeeNote($summit_id, $attendee_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $member = $this->resource_server_context->getCurrentUser();
            if (is_null($member)) return $this->error403();

            $payload = $this->getJsonPayload(SummitAttendeeNoteValidationRulesFactory::buildForAdd(), true);

            $note = $this->attendee_service->upsertAttendeeNote($summit, $member, intval($attendee_id), null, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($note, SerializerRegistry::SerializerType_Private)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @param $note_id
     * @return mixed
     */
    public function updateAttendeeNote($summit_id, $attendee_id, $note_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id, $note_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $member = $this->resource_server_context->getCurrentUser();
            if (is_null($member)) return $this->error403();

            $payload = $this->getJsonPayload(SummitAttendeeNoteValidationRulesFactory::buildForUpdate(), true);

            $note = $this->attendee_service->upsertAttendeeNote($summit, $member, intval($attendee_id), intval($note_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($note, SerializerRegistry::SerializerType_Private)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @param $note_id
     * @return mixed
     */
    public function deleteAttendeeNote($summit_id, $attendee_id, $note_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id, $note_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->attendee_service->deleteAttendeeNote($summit, intval($attendee_id), intval($note_id));

            return $this->deleted();
        });
    }
}
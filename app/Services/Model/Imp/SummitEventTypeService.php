<?php namespace App\Services;
/**
 * Copyright 2018 OpenStack Foundation
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

use App\Models\Foundation\Summit\Factories\SummitEventTypeFactory;
use App\Models\Foundation\Summit\Repositories\IDefaultSummitEventTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitEventTypeRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitEventTypeService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitEventType;

/**
 * Class SummitEventTypeService
 * @package App\Services
 */
final class SummitEventTypeService
    extends AbstractService
    implements ISummitEventTypeService
{
    /**
     * @var ISummitEventTypeRepository
     */
    private $repository;

    /**
     * @var IDefaultSummitEventTypeRepository
     */
    private $default_event_types_repository;

    /**
     * SummitEventTypeService constructor.
     * @param ISummitEventTypeRepository $repository
     * @param IDefaultSummitEventTypeRepository $default_event_types_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitEventTypeRepository        $repository,
        IDefaultSummitEventTypeRepository $default_event_types_repository,
        ITransactionService               $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
        $this->default_event_types_repository = $default_event_types_repository;
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitEventType
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws \Exception
     */
    public function addEventType(Summit $summit, array $payload)
    {
        $event_type = $this->tx_service->transaction(function () use ($summit, $payload) {

            $type = trim($payload['name']);

            if ($summit->hasEventType($type)) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Event type %s already exist on summit id %s.",
                        $type,
                        $summit->getId()
                    )
                );
            }

            $event_type = SummitEventTypeFactory::build($summit, $payload);

            if (is_null($event_type))
                throw new ValidationException
                (
                    sprintf
                    (
                        "class_name %s is invalid.",
                        $payload['class_name']
                    )
                );

               return $event_type;
        });

        return $event_type;
    }

    /**
     * @param Summit $summit
     * @param int $event_type_id
     * @param array $data
     * @return SummitEventType
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws \Exception
     */
    public function updateEventType(Summit $summit, $event_type_id, array $payload)
    {
        return $this->tx_service->transaction(function () use ($summit, $event_type_id, $payload) {

            $type = isset($payload['name']) ? trim($payload['name']) : null;

            $event_type = $summit->getEventType($event_type_id);

            if (is_null($event_type))
                throw new EntityNotFoundException
                (
                    sprintf
                    (
                        "event type id %s does not belongs to summit id %s",
                        $event_type_id,
                        $summit->getId()
                    )
                );

            if (!empty($type)) {
                $old_event_type = $summit->getEventTypeByType($type);
                if (!is_null($old_event_type) && $old_event_type->getId() != $event_type->getId()) {
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "name %s already belongs to another event type id %s",
                            $type,
                            $old_event_type->getId()
                        )
                    );
                }
            }

            return SummitEventTypeFactory::populate($event_type, $summit, $payload);

        });
    }

    /**
     * @param Summit $summit
     * @param int $event_type_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws \Exception
     */
    public function deleteEventType(Summit $summit, $event_type_id)
    {
        return $this->tx_service->transaction(function () use ($event_type_id, $summit) {

            $event_type = $summit->getEventType($event_type_id);

            if (is_null($event_type))
                throw new EntityNotFoundException
                (
                    sprintf("event type id %s does not belongs to summit id %s", $event_type_id, $summit->getId())
                );

            if ($event_type->isDefault())
                throw new ValidationException
                (
                    sprintf("event type id %s is a default one and is not allowed to be deleted", $event_type_id)
                );

            $summit_events = $event_type->getRelatedPublishedSummitEventsIds();

            if (count($summit_events) > 0) {
                throw new ValidationException
                (
                    sprintf("event type id %s could not be deleted bc its assigned to published events on summit id %s", $event_type_id, $summit->getId())
                );
            }

            $summit->removeEventType($event_type);

        });
    }

    /**
     * @param Summit $summit
     * @return SummitEventType[]
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @throws \Exception
     */
    public function seedDefaultEventTypes(Summit $summit)
    {
        return $this->tx_service->transaction(function () use ($summit) {
            $added_types = [];
            $default_types = $this->default_event_types_repository->getAll();
            foreach ($default_types as $default_type) {
                $former_type = $summit->getEventTypeByType($default_type->getType());
                if (!is_null($former_type)) continue;
                $new_type = $default_type->buildType($summit);
                $summit->addEventType($new_type);
                $added_types[] = $new_type;
            }

            return $added_types;
        });

    }

    /**
     * @param Summit $summit
     * @param int $event_type_id
     * @param int $document_id
     * @return SummitEventType
     * @throws \Exception
     */
    public function addSummitDocumentToEventType(Summit $summit, int $event_type_id, int $document_id): SummitEventType
    {
        return $this->tx_service->transaction(function () use ($summit, $event_type_id, $document_id) {
            $document = $summit->getSummitDocumentById($document_id);
            if (is_null($document))
                throw new EntityNotFoundException();

            $event_type = $summit->getEventType($event_type_id);
            if (is_null($event_type))
                throw new EntityNotFoundException();

            $document->addEventType($event_type);

            return $event_type;
        });
    }

    /**
     * @param Summit $summit
     * @param int $event_type_id
     * @param int $document_id
     * @return SummitEventType
     * @throws \Exception
     */
    public function removeSummitDocumentFromEventType(Summit $summit, int $event_type_id, int $document_id): SummitEventType
    {
        return $this->tx_service->transaction(function () use ($summit, $event_type_id, $document_id) {
            $document = $summit->getSummitDocumentById($document_id);
            if (is_null($document))
                throw new EntityNotFoundException();

            $event_type = $summit->getEventType($event_type_id);
            if (is_null($event_type))
                throw new EntityNotFoundException();

            $document->removeEventType($event_type);

            return $event_type;
        });
    }
}
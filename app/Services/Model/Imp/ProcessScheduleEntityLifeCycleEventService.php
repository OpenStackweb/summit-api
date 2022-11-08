<?php namespace App\Services\Model\Imp;
/*
 * Copyright 2022 OpenStack Foundation
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

use App\Services\Model\AbstractService;
use App\Services\Model\IProcessScheduleEntityLifeCycleEventService;
use App\Services\Utils\RabbitPublisherService;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\summit\ISummitRepository;
use models\summit\Summit;

/**
 * Class ProcessScheduleEntityLifeCycleEventService
 * @package App\Services\Model\Imp
 */
final class ProcessScheduleEntityLifeCycleEventService
    extends AbstractService
    implements IProcessScheduleEntityLifeCycleEventService

{

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var RabbitPublisherService
     */
    private $rabbit_service;

    /**
     * @param ISummitRepository $summit_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRepository   $summit_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->rabbit_service = new RabbitPublisherService('entities-updates-broker');
        $this->summit_repository = $summit_repository;
    }

    /**
     * @param string $entity_operator
     * @param int $summit_id
     * @param int $entity_id
     * @param string $entity_type
     */
    public function process(string $entity_operator, int $summit_id, int $entity_id, string $entity_type): void
    {
        $this->tx_service->transaction(function () use ($entity_operator, $summit_id, $entity_id, $entity_type) {

            Log::debug
            (
                sprintf
                (
                    "ProcessScheduleEntityLifeCycleEventService::process %s %s %s %s",
                    $summit_id,
                    $entity_id,
                    $entity_type,
                    $entity_operator
                )
            );

            if ($entity_type === 'PresentationSpeaker') {
                foreach ($this->summit_repository->getNotEnded() as $summit) {
                    if (!$summit instanceof Summit) continue;
                    if ($summit->getSpeaker($entity_id)) {
                        // speaker is present on this summit
                        $this->rabbit_service->publish(
                            [
                                'summit_id' => $summit->getId(),
                                'entity_id' => $entity_id,
                                'entity_type' => $entity_type,
                                'entity_operator' => $entity_operator
                            ]);
                    }
                }
                return;
            }

            $this->rabbit_service->publish([
                'summit_id' => $summit_id,
                'entity_id' => $entity_id,
                'entity_type' => $entity_type,
                'entity_operator' => $entity_operator
            ]);

        });
    }
}
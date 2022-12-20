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
use libs\utils\ICacheService;
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

    const CacheTTL = 5;
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var RabbitPublisherService
     */
    private $rabbit_service;

    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * @param ICacheService $cache_service
     * @param ISummitRepository $summit_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ICacheService       $cache_service,
        ISummitRepository   $summit_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->cache_service = $cache_service;
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

            $cache_key = sprintf("%s:%s:%s:%s", $summit_id, $entity_id, $entity_type, $entity_operator);

            if ($this->cache_service->exists($cache_key)) {
                Log::warning
                (
                    sprintf
                    (
                        "ProcessScheduleEntityLifeCycleEventService::process %s %s %s %s alredy processed.",
                        $summit_id,
                        $entity_id,
                        $entity_type,
                        $entity_operator
                    ));
                return;
            }

            $res = $this->cache_service->addSingleValue($cache_key, $cache_key, self::CacheTTL);
            if(!$res){
                Log::warning
                (
                    sprintf
                    (
                        "ProcessScheduleEntityLifeCycleEventService::process %s %s %s %s alredy processed.",
                        $summit_id,
                        $entity_id,
                        $entity_type,
                        $entity_operator
                    ));
                return;
            }

            if ($entity_type === 'PresentationSpeaker') {
                foreach ($this->summit_repository->getAll() as $summit) {
                    if (!$summit instanceof Summit) continue;
                    if ($summit->getSpeaker($entity_id)) {

                        Log::debug(sprintf("ProcessScheduleEntityLifeCycleEventService::process publishing speaker %s to summit %s",
                        $entity_id, $summit->getId()));
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
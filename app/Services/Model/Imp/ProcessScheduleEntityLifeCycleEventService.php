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

use App\Models\Foundation\Summit\Speakers\PresentationSpeakerAssignment;
use App\Services\Model\AbstractService;
use App\Services\Model\IProcessScheduleEntityLifeCycleEventService;
use App\Services\Utils\RabbitPublisherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use LaravelDoctrine\ORM\Facades\EntityManager;
use libs\utils\CacheRegions;
use libs\utils\ICacheService;
use libs\utils\ITransactionService;
use models\summit\ISummitRepository;
use models\summit\Presentation;
use models\summit\Summit;
use models\summit\SummitEvent;
use models\summit\SummitTicketType;

/**
 * Class ProcessScheduleEntityLifeCycleEventService
 * @package App\Services\Model\Imp
 */
final class ProcessScheduleEntityLifeCycleEventService
    extends AbstractService
    implements IProcessScheduleEntityLifeCycleEventService

{

    const CacheTTL = 2;
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
        $this->rabbit_service = null;
        $use_realtime_updates = intval(Config::get("schedule.use_realtime_updates", 1));
        Log::debug(sprintf("ProcessScheduleEntityLifeCycleEventService::__construct schedule.use_realtime_updates %s", $use_realtime_updates));

        if ($use_realtime_updates) {
            Log::debug("ProcessScheduleEntityLifeCycleEventService::__construct schedule.use_realtime_updates is enabled");
            $this->rabbit_service = new RabbitPublisherService('entities-updates-broker');
        }

        $this->summit_repository = $summit_repository;
    }

    /**
     * @param string $entity_operator
     * @param int $summit_id
     * @param int $entity_id
     * @param string $entity_type
     * @param array $params
     * @return void
     * @throws \Exception
     */
    public function process(string $entity_operator, int $summit_id, int $entity_id, string $entity_type, array $params = []): void
    {
        $this->tx_service->transaction(function () use ($entity_operator, $summit_id, $entity_id, $entity_type, $params) {

            Log::debug
            (
                sprintf
                (
                    "ProcessScheduleEntityLifeCycleEventService::process %s %s %s %s %s",
                    $summit_id,
                    $entity_id,
                    $entity_type,
                    $entity_operator,
                    json_encode($params)
                )
            );

            if ($entity_type === 'PresentationSpeakerAssignment') {
                $summit = $this->summit_repository->getById($summit_id);
                if (!$summit instanceof Summit) return;

                $repository = EntityManager::getRepository(PresentationSpeakerAssignment::class);
                $ps_assignment = $repository->find($entity_id);
                if (!$ps_assignment instanceof PresentationSpeakerAssignment) return;
                $presentation = $ps_assignment->getPresentation();

                if ($presentation instanceof Presentation) {
                    Log::debug(sprintf("ProcessScheduleEntityLifeCycleEventService::process presentation %s from summit %s",
                        $entity_id, $summit->getId()));

                    $entity_id = $presentation->getId();
                    $entity_type = 'Presentation';
                    $entity_operator = 'UPDATE';
                }

            }

            // clear cache region...

            $cache_region_key = null;
            if ($entity_type === 'Presentation' || $entity_type === 'SummitEvent') {
                $cache_region_key = CacheRegions::getCacheRegionFor(CacheRegions::CacheRegionEvents, $entity_id);
            }
            if($entity_type === 'Summit'){
                $cache_region_key = CacheRegions::getCacheRegionFor(CacheRegions::CacheRegionSummits, $entity_id);
            }
            if($entity_type === 'PresentationCategory'){
                $cache_region_key = CacheRegions::getCacheRegionFor(CacheRegions::CacheRegionSummits, $summit_id);
            }
            if($entity_type === 'PresentationSpeaker'){
                $cache_region_key = CacheRegions::getCacheRegionFor(CacheRegions::CacheRegionSpeakers, $entity_id);
            }

            if (!empty($cache_region_key)) {
                Cache::tags($cache_region_key)->flush();
                if($this->cache_service->exists($cache_region_key)){
                    Log::debug(sprintf("ProcessScheduleEntityLifeCycleEventService::process will clear cache region %s", $cache_region_key));
                    $region_data = $this->cache_service->getSingleValue($cache_region_key);
                    if (!empty($region_data)) {
                        $region = json_decode(gzinflate($region_data), true);
                        Log::debug(sprintf("ProcessScheduleEntityLifeCycleEventService::process got payload %s region %s", json_encode($region), $cache_region_key));
                        foreach ($region as $key => $val) {
                            Log::debug(sprintf("ProcessScheduleEntityLifeCycleEventService::process clearing cache key %s", $key));
                            $this->cache_service->delete($key);
                            $this->cache_service->delete($key . 'generated');
                        }
                        $this->cache_service->delete($cache_region_key);
                    }
                }
            }

            if (is_null($this->rabbit_service)) {
                Log::debug("ProcessScheduleEntityLifeCycleEventService::process rabbit service is disabled.");
                return;
            }

            $cache_key = sprintf("%s:%s:%s:%s", $summit_id, $entity_id, $entity_type, $entity_operator);

            if ($this->cache_service->exists($cache_key)) {
                Log::warning
                (
                    sprintf
                    (
                        "ProcessScheduleEntityLifeCycleEventService::process %s %s %s %s already processed.",
                        $summit_id,
                        $entity_id,
                        $entity_type,
                        $entity_operator
                    ));
                return;
            }

            $res = $this->cache_service->addSingleValue($cache_key, $cache_key, self::CacheTTL);
            if (!$res) {
                Log::warning
                (
                    sprintf
                    (
                        "ProcessScheduleEntityLifeCycleEventService::process %s %s %s %s can not add value to cache ( already exists ).",
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

            if ($entity_type === 'PresentationSpeakerAssignment') {
                $summit = $this->summit_repository->getById($summit_id);
                if (!$summit instanceof Summit) return;

                $repository = EntityManager::getRepository(PresentationSpeakerAssignment::class);
                $ps_assignment = $repository->find($entity_id);
                if (!$ps_assignment instanceof PresentationSpeakerAssignment) return;
                $presentation = $ps_assignment->getPresentation();

                if ($presentation instanceof Presentation) {
                    Log::debug(sprintf("ProcessScheduleEntityLifeCycleEventService::process presentation %s from summit %s",
                        $entity_id, $summit->getId()));

                    $this->rabbit_service->publish(
                        [
                            'summit_id' => $summit->getId(),
                            'entity_id' => $presentation->getId(),
                            'entity_type' => 'Presentation',
                            'entity_operator' => 'UPDATE'
                        ]);
                }
                return;
            }

            if ($entity_type === 'SummitTicketType') {
                $summit = $this->summit_repository->getById($summit_id);
                if (!$summit instanceof Summit) return;

                $ticket_type = $summit->getTicketTypeById($entity_id);
                if (!$ticket_type instanceof SummitTicketType) return;

                $this->rabbit_service->publish(
                    [
                        'summit_id' => $summit->getId(),
                        'entity_id' => $summit->getId(),
                        'entity_type' => 'Summit',
                        'entity_operator' => 'UPDATE'
                    ]);

                return;
            }

            if ($entity_type === Presentation::PresentationOverflowEntityType) {
                Log::debug(sprintf("ProcessScheduleEntityLifeCycleEventService::process presentation overflow %s from summit %s",
                    $entity_id, $summit_id));
                $summit = $this->summit_repository->getById($summit_id);
                if (!$summit instanceof Summit) {
                    Log::warning(sprintf("ProcessScheduleEntityLifeCycleEventService::process summit %s not found", $summit_id));
                    return;
                }
                $repository = EntityManager::getRepository(SummitEvent::class);
                $summit_event = $repository->find($entity_id);
                if (!$summit_event instanceof SummitEvent) {
                    Log::warning(sprintf("ProcessScheduleEntityLifeCycleEventService::process summit event %s not found", $entity_id));
                    return;
                }

                Log::debug
                (
                    sprintf
                    (
                        "ProcessScheduleEntityLifeCycleEventService::process publishing summit %s OVERFLOW entity id %s",
                        $summit_id,
                        $entity_id
                    )
                );

                $this->rabbit_service->publish(
                    [
                        'summit_id' => $summit->getId(),
                        'entity_id' => $summit_event->getId(),
                        'entity_type' => $entity_type,
                        'entity_operator' => 'UPDATE',
                        'params' => $params
                    ]);

                return;
            }


            Log::debug(sprintf("ProcessScheduleEntityLifeCycleEventService::process publishing summit %s entity id %s",
                $summit_id, $entity_id));

            $this->rabbit_service->publish([
                'summit_id' => $summit_id,
                'entity_id' => $entity_id,
                'entity_type' => $entity_type,
                'entity_operator' => $entity_operator
            ]);

        });
    }
}
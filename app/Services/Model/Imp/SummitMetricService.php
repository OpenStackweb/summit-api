<?php namespace App\Services\Model\Imp;
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
use App\Models\Foundation\Summit\Factories\SummitMetricFactory;
use App\Models\Foundation\Summit\Repositories\ISummitMetricRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitMetricService;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\ISummitEventRepository;
use models\summit\Summit;
use models\summit\SummitEvent;
use models\summit\SummitEventAttendanceMetric;
use models\summit\SummitMetric;
use models\summit\SummitSponsorMetric;
/**
 * Class SummitMetricService
 * @package App\Services\Model\Imp
 */
final class SummitMetricService
    extends AbstractService
    implements ISummitMetricService
{

    /**
     * @var ISummitMetricRepository
     */
    private $repository;

    /**
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * SummitMetricService constructor.
     * @param ISummitMetricRepository $repository
     * @param ISummitEventRepository $event_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitMetricRepository $repository,
        ISummitEventRepository $event_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
        $this->event_repository = $event_repository;
    }

    /**
     * @param Summit $summit
     * @param Member $current_member
     * @param array $payload
     * @return SummitMetric
     * @throws \Exception
     */
    public function enter(Summit $summit, Member $current_member, array $payload): SummitMetric
    {
        Log::debug(sprintf("SummitMetricService::enter summit %s member %s payload %s", $summit->getId(), $current_member->getId(), json_encode($payload)));
        return $this->tx_service->transaction(function () use ($summit, $current_member, $payload) {
            $metric = SummitMetricFactory::build($current_member, $payload);
            $metric->setMember($current_member);

            $source_id = null;
            if(isset($payload['source_id']))
                $source_id = intval($payload['source_id']);

            $formerMetric = $this->repository->getNonAbandoned($current_member, $metric->getType(), $source_id);

            if(!is_null($formerMetric)){
                // mark as leave
                Log::debug(sprintf("SummitMetricService::enter there is a former metric (%s)", $formerMetric->getId()));
                $formerMetric->abandon();
            }

            if($metric instanceof SummitEventAttendanceMetric){
                if(!isset($payload['source_id'])){
                    throw new ValidationException("source_id param is missing.");
                }
                $event_id = intval($payload['source_id']);
                $event = $this->event_repository->getById($event_id);

                if (is_null($event) || !$event instanceof SummitEvent) {
                    throw new EntityNotFoundException(sprintf("Event %s does not belongs to summit %s.", $event_id, $summit->getId()));
                }

                if (!$event->isPublished()) {
                    throw new ValidationException(sprintf("Event %s is not published.", $event->getId()));
                }

                $metric->setEvent($event);
            }

            if($metric instanceof SummitSponsorMetric){
                if(!isset($payload['source_id'])){
                    throw new ValidationException("source_id param is missing.");
                }
                $sponsor_id = intval($payload['source_id']);

                $sponsor = $summit->getSummitSponsorById($sponsor_id);

                if (is_null($sponsor)) {
                    throw new EntityNotFoundException(sprintf("Sponsor %s does not belongs to summit %s.", $sponsor_id, $summit->getId()));
                }
                $metric->setSponsor($sponsor);
            }

            $summit->addMetric($metric);
            return $metric;
        });
    }

    /**
     * @param Summit $summit
     * @param Member $current_member
     * @param array $payload
     * @return SummitMetric
     * @throws \Exception
     */
    public function leave(Summit $summit, Member $current_member, array $payload): SummitMetric
    {
        Log::debug(sprintf("SummitMetricService::leave summit %s member %s payload %s", $summit->getId(), $current_member->getId(), json_encode($payload)));

        return $this->tx_service->transaction(function () use ($summit, $current_member, $payload) {

            $source_id = null;
            if(isset($payload['source_id']))
                $source_id = intval($payload['source_id']);
            $formerMetric = $this->repository->getNonAbandoned($current_member, trim($payload['type']), $source_id);
            if(!$formerMetric)
                throw new ValidationException(sprintf("User %s has not a pending %s metric", $current_member->getId(), $payload['type']));

            $formerMetric->abandon();

            return $formerMetric;
        });
    }
}
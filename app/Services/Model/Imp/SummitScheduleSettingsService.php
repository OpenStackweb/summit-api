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
use App\Models\Foundation\Summit\Factories\SummitScheduleConfigFactory;
use App\Models\Foundation\Summit\Repositories\ISummitScheduleConfigRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitScheduleSettingsService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitScheduleConfig;
use models\summit\SummitScheduleFilterElementConfig;

/**
 * Class SummitScheduleSettingsService
 * @package App\Services\Model\Imp
 */
final class SummitScheduleSettingsService
    extends AbstractService
    implements ISummitScheduleSettingsService
{
    /**
     * @param ISummitScheduleConfigRepository $repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitScheduleConfig|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function add(Summit $summit, array $payload): ?SummitScheduleConfig
    {
       return $this->tx_service->transaction(function() use($summit, $payload){

           $config = SummitScheduleConfigFactory::build($payload);

           $summit->addScheduleSetting($config);

           return $config;
       });
    }

    /**
     * @param Summit $summit
     * @param int $config_id
     * @param array $payload
     * @return SummitScheduleConfig|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function update(Summit $summit, int $config_id, array $payload): ?SummitScheduleConfig
    {
        return $this->tx_service->transaction(function() use($summit, $config_id, $payload){
            $config = $summit->getScheduleSettingById($config_id);
            if(is_null($config))
                throw new EntityNotFoundException(sprintf("Schedule config setting %s not found on Summit %s", $config_id, $summit->getId()));

            SummitScheduleConfigFactory::populate($config, $payload);

            return $config;
        });
    }

    /**
     * @param Summit $summit
     * @param int $config_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function delete(Summit $summit, int $config_id): void
    {
         $this->tx_service->transaction(function() use($summit, $config_id){
            $config = $summit->getScheduleSettingById($config_id);
            if(is_null($config))
                throw new EntityNotFoundException(sprintf("Schedule config setting %s not found on Summit %s", $config_id, $summit->getId()));

            $summit->removeScheduleSetting($config);
        });
    }

    /**
     * @param Summit $summit
     * @param int $config_id
     * @param array $payload
     * @return SummitScheduleFilterElementConfig|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addFilter(Summit $summit, int $config_id, array $payload): ?SummitScheduleFilterElementConfig
    {
        // TODO: Implement addFilter() method.
    }

    /**
     * @param Summit $summit
     * @param int $config_id
     * @param int $filter_id
     * @param array $payload
     * @return SummitScheduleFilterElementConfig|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateFilter(Summit $summit, int $config_id, int $filter_id, array $payload): ?SummitScheduleFilterElementConfig
    {
        // TODO: Implement updateFilter() method.
    }
}
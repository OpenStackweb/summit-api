<?php namespace App\Http\Controllers;
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

use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Models\Foundation\Summit\Repositories\ISummitScheduleConfigRepository;
use App\Services\Model\ISummitScheduleSettingsService;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitScheduleConfig;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\PagingResponse;

/**
 * Class OAuth2SummitScheduleSettingsApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitScheduleSettingsApiController extends OAuth2ProtectedController
{

    use AddSummitChildElement;

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    use ParametrizedAddEntity;

    use ParametrizedUpdateEntity;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitScheduleSettingsService
     */
    private $service;

    /**
     * @param ISummitRepository $summit_repository
     * @param ISummitScheduleConfigRepository $repository
     * @param ISummitScheduleSettingsService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitScheduleConfigRepository $repository,
        ISummitScheduleSettingsService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->service = $service;
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @inheritDoc
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->add($summit, $payload);
    }

    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return SummitScheduleConfigRulesFactory::build($payload, false);
    }

    /**
     * @inheritDoc
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->delete($summit, intval($child_id));
    }

    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getScheduleSettingById(intval($child_id));
    }

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitScheduleConfigRulesFactory::build($payload, true);
    }

    /**
     * @inheritDoc
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->update($summit, intval($child_id), $payload);
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'key'        => ['=@', '=='],
            'is_enabled' => ['=='],
            'is_my_schedule' => ['=='],
            'only_events_with_attendee_access' =>  ['=='],
            'color_source' => ['==']
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'key' => 'sometimes|required|string',
            'is_enabled' => 'sometimes|required|boolean',
            'is_my_schedule' => 'sometimes|required|boolean',
            'only_events_with_attendee_access'=> 'sometimes|required|boolean',
            'color_source' => 'sometimes|string|in:'.implode(',', SummitScheduleConfig::AllowedColorSource),
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'key',
        ];
    }

    protected function serializerType():string{
        return SerializerRegistry::SerializerType_Private;
    }

    protected function addSerializerType():string{
        return SerializerRegistry::SerializerType_Private;
    }

    protected function updateSerializerType():string{
        return SerializerRegistry::SerializerType_Private;
    }

    public function getChildSerializer():string{
        return SerializerRegistry::SerializerType_Private;
    }

    public function getMetadata($summit_id){

    }

    public function addFilter($summit_id, $config_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit, intval($config_id)];

        return $this->_add(
            function ($payload) {
                return SummitScheduleFilterElementConfigValidationRulesFactory::build($payload);
            },
            function ($payload, $summit, $id){
                return $this->service->addFilter($summit, $id, $payload);
            },
            ...$args
        );
    }

    public function updateFilter($summit_id, $config_id, $filter_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit, intval($config_id)];

        return $this->_update($filter_id, function($payload){
            return SummitScheduleFilterElementConfigValidationRulesFactory::build($payload, false);
        },
            function($filter_id, $payload, $summit, $config_id){
                return $this->service->updateFilter
                (
                    $summit,
                    $config_id,
                    $filter_id,
                    $payload
                );
            }, ...$args);
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     * @throws \Exception
     */
    public function seedDefaults($summit_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $list = $this->service->seedDefaults($summit);

            $response = new PagingResponse
            (
                count($list),
                count($list),
                1,
                1,
                $list
            );

            return $this->created($response->toArray(
                self::getExpands(),
                self::getFields(),
                self::getRelations()
            ));
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404(array('message'=> $ex->getMessage()));
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
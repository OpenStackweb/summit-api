<?php namespace App\Http\Controllers;
/**
 * Copyright 2021 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\IPresentationActionTypeRepository;
use App\Services\Model\ISummitPresentationActionTypeService;
use Illuminate\Support\Facades\Input;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;

/**
 * Class OAuth2SummitPresentationActionTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitPresentationActionTypeApiController
    extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitPresentationActionTypeService
     */
    private $service;

    /**
     * OAuth2SummitPresentationActionTypeApiController constructor.
     * @param ISummitPresentationActionTypeService $service
     * @param IPresentationActionTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitPresentationActionTypeService $service,
        IPresentationActionTypeRepository $repository,
        ISummitRepository $summit_repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    use ParametrizedGetAll;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

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
        return SummitPresentationActionTypeValidationRulesFactory::build($payload, false);
    }

    /**
     * @inheritDoc
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @inheritDoc
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->delete($summit, $child_id);
    }

    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getPresentationActionTypeById($child_id);
    }

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitPresentationActionTypeValidationRulesFactory::build($payload, true);
    }

    /**
     * @inheritDoc
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->update($summit, $child_id, $payload);
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySummit($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '=='],
                    'label' => ['=@', '=='],
                    'is_enabled' => ['=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'label' => 'sometimes|string',
                    'is_enabled' => 'sometimes|boolean',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'order',
                    'label',
                    'is_enabled'
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySummitCSV($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'name' => ['=@', '=='],
                    'label' => ['=@', '=='],
                    'is_enabled' => ['=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'label' => 'sometimes|string',
                    'is_enabled' => 'sometimes|boolean',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'order',
                    'label',
                    'is_enabled'
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            function () {
                return [
                    'created' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                ];
            },
            function () use ($summit) {
                $allowed_columns = [
                    'id',
                    'created',
                    'last_edited',
                    'name',
                    'label',
                    'is_enabled',
                    'order',
                ];

                $columns_param = Input::get("columns", "");
                $columns = [];
                if (!empty($columns_param))
                    $columns = explode(',', $columns_param);
                $diff = array_diff($columns, $allowed_columns);
                if (count($diff) > 0) {
                    throw new ValidationException(sprintf("columns %s are not allowed!", implode(",", $diff)));
                }
                if (empty($columns))
                    $columns = $allowed_columns;
                return $columns;
            },
            sprintf('summit_presentation_action_types-%s', $summit_id)
        );
    }
}
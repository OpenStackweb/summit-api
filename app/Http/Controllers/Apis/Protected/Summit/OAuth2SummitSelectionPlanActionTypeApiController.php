<?php namespace App\Http\Controllers;
/**
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

use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Summit\Repositories\ISelectionPlanActionTypeRepository;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Services\Model\ISummitSelectionPlanActionTypeService;
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
 * Class OAuth2SummitSelectionPlanActionTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSelectionPlanActionTypeApiController
    extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitSelectionPlanActionTypeService
     */
    private $service;

    /**
     * OAuth2SummitSelectionPlanActionTypeApiController constructor.
     * @param ISummitSelectionPlanActionTypeService $service
     * @param ISelectionPlanActionTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitSelectionPlanActionTypeService $service,
        ISelectionPlanActionTypeRepository $repository,
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

    use GetSelectionPlanChildElementById;

    use AddSelectionPlanChildElement;

    use UpdateSelectionPlanChildElement;

    use DeleteSelectionPlanChildElement;

    /**
     * @inheritDoc
     * @throws ValidationException
     */
    protected function addChild(SelectionPlan $selection_plan, array $payload): IEntity
    {
        return $this->service->add($selection_plan, $payload);
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
    protected function deleteChild(SelectionPlan $selection_plan, $child_id): void
    {
        $this->service->delete($selection_plan, $child_id);
    }

    /**
     * @inheritDoc
     */
    protected function getChildFromSelectionPlan(SelectionPlan $selection_plan, $child_id): ?IEntity
    {
        return $selection_plan->getSelectionPlanActionTypeById($child_id);
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
    protected function updateChild(SelectionPlan $selection_plan, int $child_id, array $payload): IEntity
    {
        return $this->service->update($selection_plan, $child_id, $payload);
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySelectionPlan($summit_id, $selection_plan_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
        if (is_null($selection_plan)) return $this->error404();

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
            function ($filter) use ($summit, $selection_plan) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', $selection_plan->getId()));
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
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySelectionPlanCSV($summit_id, $selection_plan_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
        if (is_null($selection_plan)) return $this->error404();

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
            function ($filter) use ($summit, $selection_plan) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', $selection_plan->getId()));
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
            sprintf('summit_selection_plan_action_types-%s', $summit_id)
        );
    }
}
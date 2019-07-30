<?php namespace App\Http\Controllers;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitOrderExtraQuestionTypeRepository;
use App\Services\Model\ISummitOrderExtraQuestionTypeService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use Exception;
/**
 * Class OAuth2SummitOrderExtraQuestionTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitOrderExtraQuestionTypeApiController
    extends OAuth2ProtectedController
{

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitOrderExtraQuestionTypeService
     */
    private $service;

    /**
     * OAuth2SummitSponsorApiController constructor.
     * @param ISummitOrderExtraQuestionTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitOrderExtraQuestionTypeService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitOrderExtraQuestionTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISummitOrderExtraQuestionTypeService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        $this->repository = $repository;
    }

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getMetadata($summit_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->repository->getQuestionsMetadata($summit)
        );
    }

    /**
     * @return array
     */
    protected function getFilterRules():array{
        return [
            'name'  => ['==', '=@'],
            'type'  => ['==', '=@'],
            'usage' => ['==', '=@'],
            'label' => ['==', '=@'],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'name'  => 'sometimes|required|string',
            'type'  => 'sometimes|required|string',
            'usage' => 'sometimes|required|string',
            'label' => 'sometimes|required|string',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'name',
            'label',
            'order',
        ];
    }

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
       return SummitOrderExtraQuestionTypeValidationRulesFactory::build($payload);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
       return $this->service->addOrderExtraQuestion($summit, $payload);
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->deleteOrderExtraQuestion($summit, $child_id);
    }

    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
       return $summit->getOrderExtraQuestionById($child_id);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitOrderExtraQuestionTypeValidationRulesFactory::build($payload, true);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     * @throws \models\exceptions\EntityNotFoundException
     * @throws \models\exceptions\ValidationException
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updateOrderExtraQuestion($summit, $child_id, $payload);
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addQuestionValue($summit_id, $question_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $data = Input::json();
            $payload = $data->all();
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'label' => 'sometimes|string',
                'value' => 'required|string',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $value = $this->service->addOrderExtraQuestionValue($summit, $question_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($value)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateQuestionValue($summit_id, $question_id, $value_id){

        try {
            if(!Request::isJson()) return $this->error400();
            $data = Input::json();
            $payload = $data->all();
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'label' => 'sometimes|string',
                'value' => 'sometimes|string',
                'order' => 'sometimes|integer|min:1'
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $value = $this->service->updateOrderExtraQuestionValue($summit, $question_id, $value_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($value)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteQuestionValue($summit_id, $question_id, $value_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteOrderExtraQuestionValue($summit, $question_id, $value_id);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
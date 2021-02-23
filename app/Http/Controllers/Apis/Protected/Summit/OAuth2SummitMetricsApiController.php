<?php namespace App\Http\Controllers;
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
use App\Services\Model\ISummitMetricService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitMetricType;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use Exception;
/**
 * Class OAuth2SummitMetricsApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitMetricsApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitMetricService
     */
    private $service;

    /**
     * OAuth2SummitMembersApiController constructor.
     * @param IMemberRepository $member_repository
     * @param ISummitRepository $summit_repository
     * @param ISummitMetricService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IMemberRepository $member_repository,
        ISummitRepository $summit_repository,
        ISummitMetricService $service,
        IResourceServerContext $resource_server_context
    ) {
        parent::__construct($resource_server_context);
        $this->summit_repository  = $summit_repository;
        $this->repository         = $member_repository;
        $this->service = $service;
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function enter($summit_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = Request::all();
            if(Request::isJson()){
                $payload = Request::json()->all();
            }

            $validation = Validator::make($payload,
            [
                    'type' => 'required|string|in:'.implode(",", ISummitMetricType::ValidTypes),
                    'source_id' => 'sometimes|integer',
                    'location' => 'sometimes|string',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();
                $ex = new ValidationException();
                $ex->setMessages($messages);
                throw $ex;
            }

            $metric = $this->service->enter($summit, $current_member, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function leave($summit_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = Request::all();
            if(Request::isJson()){
                $payload = Request::json()->all();
            }

            $validation = Validator::make($payload,
            [
                    'type' => 'required|string|in:'.implode(",", ISummitMetricType::ValidTypes),
                    'source_id' => 'sometimes|integer',
                    'location' => 'sometimes|string',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();
                $ex = new ValidationException();
                $ex->setMessages($messages);
                throw $ex;
            }

            $metric = $this->service->leave($summit, $current_member, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function enterToEvent($summit_id, $member_id, $event_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $metric = $this->service->enter($summit, $current_member, [
                'type' => ISummitMetricType::Event,
                'source_id' => intval($event_id)
            ]);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function leaveFromEvent($summit_id, $member_id, $event_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $metric = $this->service->leave($summit, $current_member, [
                'type' => ISummitMetricType::Event,
                'source_id' => intval($event_id)
            ]);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
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
use App\Models\Foundation\Summit\Registration\IBuildDefaultPaymentGatewayProfileStrategy;
use App\Services\Model\ILocationService;
use App\Services\Model\IProcessPaymentService;
use App\Services\Model\ISummitOrderService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use models\oauth2\IResourceServerContext;
use models\summit\IPaymentConstants;
use models\summit\ISummitRepository;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Exception;
use OpenApi\Attributes as OA;

/**
 * Class PaymentGatewayWebHookController
 * @package App\Http\Controllers
 */
final class PaymentGatewayWebHookController extends JsonController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IResourceServerContext
     */
    private $resource_server_context;

    /**
     * @var ILocationService
     */
    private $location_service;

    /**
     * @var ISummitOrderService
     */
    private $order_service;

    /**
     * @var IBuildDefaultPaymentGatewayProfileStrategy
     */
    private $default_payment_gateway_strategy;

    /**
     * PaymentGatewayWebHookController constructor.
     * @param ISummitRepository $summit_repository
     * @param IResourceServerContext $resource_server_context
     * @param ILocationService $location_service
     * @param ISummitOrderService $order_service
     * @param IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IResourceServerContext $resource_server_context,
        ILocationService $location_service,
        ISummitOrderService $order_service,
        IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy
    )
    {
        $this->summit_repository = $summit_repository;
        $this->resource_server_context = $resource_server_context;
        $this->location_service = $location_service;
        $this->order_service = $order_service;
        $this->default_payment_gateway_strategy = $default_payment_gateway_strategy;;
    }

    /**
     * @param string $application_type
     * @return IProcessPaymentService|null
     */
    private function getProcessPaymentService(string $application_type):?IProcessPaymentService {
        if($application_type == IPaymentConstants::ApplicationTypeRegistration)
            return $this->order_service;
        if($application_type == IPaymentConstants::ApplicationTypeBookableRooms)
            return $this->location_service;
        return null;
    }

    /**
     * @param $application_type
     * @param LaravelRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: "/api/public/v1/summits/all/payments/{application_name}/confirm",
        summary: "Generic payment gateway webhook confirmation",
        description: "Handles payment gateway webhook callbacks for a given application type.",
        operationId: "genericConfirm",
        tags: ["PaymentGatewayHook"],
        security: [],
        parameters: [
            new OA\Parameter(
                name: "application_name",
                in: "path",
                required: true,
                description: "Application name (e.g. Show admin)",
                schema: new OA\Schema(type: "string")
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/PaymentGatewayWebhookRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Payment processed successfully"
            ),
            new OA\Response(
                response: Response::HTTP_ALREADY_REPORTED,
                description: "Already reported"
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: "Payload error"
            ),
            new OA\Response(
                response: Response::HTTP_PRECONDITION_FAILED,
                description: "Precondition failed - missing configuration or invalid data"
            )
        ]
    )]
    public function genericConfirm($application_type, LaravelRequest $request){
        try {

            Log::debug(sprintf("PaymentGatewayWebHookController::genericConfirm application_type %s ", $application_type));

            // get api
            $paymentGatewayApi = $this->default_payment_gateway_strategy->build($application_type);

            if(is_null($paymentGatewayApi)) {
                Log::debug(sprintf("PaymentGatewayWebHookController::genericConfirm application_type %s profile payment not found.", $application_type));
                return $this->error412([sprintf("application_type %s profile payment not found.", $application_type)]);
            }

            $service = $this->getProcessPaymentService($application_type);

            if(is_null($service)) {
                Log::debug(sprintf("PaymentGatewayWebHookController::genericConfirm application_type %s service not found.", $application_type));
                return $this->error412([sprintf("application_type %s service not found.", $application_type)]);
            }

            $payload = $paymentGatewayApi->buildPaymentGatewayApi()->processCallback($request);
            $cart_id = $payload['cart_id'] ?? null;
            if(is_null($cart_id)) {
                Log::debug("PaymentGatewayWebHookController::genericConfirm cart id is null.");
                return $this->error412("cart id is null");
            }

            $lock = Cache::lock("stripe:pi:{$cart_id}", 30); // 30s is enough
            if (!$lock->get()) {
                Log::warning("PaymentGatewayWebHookController::genericConfirm  Skip concurrent webhook for {$cart_id}");
                return $this->ok(); // idempotent no-op
            }
            Log::debug(sprintf("PaymentGatewayWebHookController::genericConfirm  cart id %s processing payment.", $cart_id));

            $service->processPayment($payload);

            return $this->ok();
        }
        catch(EntityNotFoundException $ex){
            Log::warning($ex);
            return $this->response2XX(208, ['error' => 'already reported']);
        }
        catch(ValidationException $ex){
            Log::warning($ex);
            return $this->error412(["error" => 'payload error']);
        }
        catch (Exception $ex){
            Log::error($ex);
            return $this->error400(["error" => 'payload error']);
        }
        return $this->error400(["error" => 'invalid event type']);
    }

    /**
     * @param $summit_id
     * @param $application_type
     * @param LaravelRequest $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: "/api/public/v1/summits/{summit_id}/payments/{application_name}/confirm",
        summary: "Summit payment gateway webhook confirmation",
        description: "Handles payment gateway webhook callbacks for a given summit and application type.",
        operationId: "confirm",
        tags: ["PaymentGatewayHook"],
        security: [],
        parameters: [
            new OA\Parameter(
                name: "summit_id",
                in: "path",
                required: true,
                description: "Summit identifier",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "application_name",
                in: "path",
                required: true,
                description: "Application Name (e.g ShowAdmin)",
                schema: new OA\Schema(type: "string")
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/PaymentGatewayWebhookRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Payment processed successfully"
            ),
            new OA\Response(
                response: Response::HTTP_ALREADY_REPORTED,
                description: "Already reported"
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: "Payload error"
            ),
            new OA\Response(
                response: Response::HTTP_PRECONDITION_FAILED,
                description: "Precondition failed - missing configuration or invalid data"
            )
        ]
    )]
    public function confirm($summit_id, $application_type, LaravelRequest $request){

        try {

            Log::debug(sprintf("PaymentGatewayWebHookController::confirm summit %s application_type %s ", $summit_id, $application_type));

            // get current summit
            $summit = SummitFinderStrategyFactory::build
            (
                $this->summit_repository,
                $this->resource_server_context
            )->find($summit_id);

            if (!$summit instanceof Summit){
                Log::debug(sprintf("PaymentGatewayWebHookController::confirm summit %s not found.", $summit_id));
                return $this->error412([sprintf("application_type %s summit not found.", $application_type)]);
            }

            // get api
            $paymentGatewayApi = $summit->getPaymentGateWayPerApp($application_type, $this->default_payment_gateway_strategy);

            if(is_null($paymentGatewayApi)) {
                Log::debug(sprintf("PaymentGatewayWebHookController::confirm summit %s profile payment not found.", $summit_id));
                return $this->error412([sprintf("application_type %s summit not found.", $application_type)]);
            }

            $service = $this->getProcessPaymentService($application_type);

            if(is_null($service)) {
                Log::debug(sprintf("PaymentGatewayWebHookController::confirm summit %s service not found.", $summit_id));
                return $this->error412([sprintf("application_type %s service not found.", $application_type)]);
            }

            $payload = $paymentGatewayApi->processCallback($request);
            $cart_id = $payload['cart_id'] ?? null;
            if(is_null($cart_id)) {
                Log::debug(sprintf("PaymentGatewayWebHookController::confirm summit %s cart id is null.", $summit_id));
                return $this->error412("cart id is null");
            }

            $lock = Cache::lock("stripe:pi:{$cart_id}", 30); // 30s is enough
            if (!$lock->get()) {
                Log::warning("PaymentGatewayWebHookController::confirm  Skip concurrent webhook for {$cart_id}");
                return $this->ok(); // idempotent no-op
            }
            Log::debug(sprintf("PaymentGatewayWebHookController::confirm summit %s cart id %s processing payment.", $summit_id, $cart_id));
            $service->processPayment($payload, $summit);
            return $this->ok();
        }
        catch(EntityNotFoundException $ex){
            Log::warning($ex);
            return $this->response2XX(208, ['error' => 'already reported']);
        }
        catch(ValidationException $ex){
            Log::warning($ex);
            return $this->error412(["error" => 'payload error']);
        }
        catch (Exception $ex){
            Log::error($ex);
            return $this->error400(["error" => 'payload error']);
        }
        return $this->error400(["error" => 'invalid event type']);
    }

}

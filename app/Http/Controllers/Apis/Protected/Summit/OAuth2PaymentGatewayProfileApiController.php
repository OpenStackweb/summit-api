<?php

namespace App\Http\Controllers;

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
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\IPaymentGatewayProfileRepository;
use App\Security\SummitScopes;
use App\Services\Model\IPaymentGatewayProfileService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class OAuth2PaymentGatewayProfileApiController
 * @package App\Http\Controller
 */
final class OAuth2PaymentGatewayProfileApiController extends OAuth2ProtectedController
{

    /**
     * @var IPaymentGatewayProfileService
     */
    private $service;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * OAuth2PaymentGatewayProfileApiController constructor.
     * @param IPaymentGatewayProfileRepository $repository
     * @param ISummitRepository $summit_repository
     * @param IPaymentGatewayProfileService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IPaymentGatewayProfileRepository $repository,
        ISummitRepository $summit_repository,
        IPaymentGatewayProfileService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    // OpenAPI Documentation

    #[OA\Get(
        path: '/api/v1/summits/{id}/payment-gateway-profiles',
        summary: 'Get all payment gateway profiles for a summit',
        description: 'Retrieves a paginated list of payment gateway profiles configured for a specific summit. Payment profiles manage payment processing for registrations and bookable rooms.',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [['summit_payment_gateway_oauth2' =>
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadPaymentProfiles
        ]],
        tags: ['Payment Gateway Profiles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number for pagination',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', example: 10, maximum: 100)
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions. Format: field<op>value. Available fields: application_type (=@, ==), active (==). Operators: == (equals), =@ (starts with)',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'application_type==Registration')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available fields: id, application_type. Use "-" prefix for descending order.',
                schema: new OA\Schema(type: 'string', example: 'id')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment gateway profiles retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedPaymentGatewayProfilesResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Get(
        path: '/api/v1/summits/{id}/payment-gateway-profiles/{payment_profile_id}',
        summary: 'Get a payment gateway profile by ID',
        description: 'Retrieves detailed information about a specific payment gateway profile.',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [['summit_payment_gateway_oauth2' =>
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadPaymentProfiles
        ]],
        tags: ['Payment Gateway Profiles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'payment_profile_id',
                in: 'path',
                required: true,
                description: 'Payment Gateway Profile ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment gateway profile retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentGatewayProfile')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Post(
        path: '/api/v1/summits/{id}/payment-gateway-profiles',
        summary: 'Create a new payment gateway profile',
        description: 'Creates a new payment gateway profile for the summit. Supports Stripe and LawPay providers.',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [['summit_payment_gateway_oauth2' =>
            SummitScopes::WriteSummitData,
            SummitScopes::WritePaymentProfiles
        ]],
        tags: ['Payment Gateway Profiles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PaymentGatewayProfileCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Payment gateway profile created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentGatewayProfile')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Put(
        path: '/api/v1/summits/{id}/payment-gateway-profiles/{payment_profile_id}',
        summary: 'Update a payment gateway profile',
        description: 'Updates an existing payment gateway profile.',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [['summit_payment_gateway_oauth2' =>
            SummitScopes::WriteSummitData,
            SummitScopes::WritePaymentProfiles
        ]],
        tags: ['Payment Gateway Profiles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'payment_profile_id',
                in: 'path',
                required: true,
                description: 'Payment Gateway Profile ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PaymentGatewayProfileUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment gateway profile updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentGatewayProfile')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Delete(
        path: '/api/v1/summits/{id}/payment-gateway-profiles/{payment_profile_id}',
        summary: 'Delete a payment gateway profile',
        description: 'Deletes an existing payment gateway profile from the summit.',
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [['summit_payment_gateway_oauth2' =>
            SummitScopes::WriteSummitData,
            SummitScopes::WritePaymentProfiles
        ]],
        tags: ['Payment Gateway Profiles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'payment_profile_id',
                in: 'path',
                required: true,
                description: 'Payment Gateway Profile ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Payment gateway profile deleted successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

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
        return $this->service->addPaymentProfile($summit, $payload);
    }

    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return PaymentGatewayProfileValidationRulesFactory::build($payload, false);
    }

    /**
     * @inheritDoc
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->deletePaymentProfile($summit, $child_id);
    }

    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getPaymentProfileById($child_id);
    }

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return PaymentGatewayProfileValidationRulesFactory::build($payload, true);
    }

    /**
     * @inheritDoc
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updatePaymentProfile($summit, $child_id, $payload);
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'application_type' => ['=@', '=='],
            'active'           => ['=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'application_type' => 'sometimes|required|string',
            'active'           => 'sometimes|required|boolean',
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'application_type',
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

    public function getChildSerializer(){
        return SerializerRegistry::SerializerType_Private;
    }
}

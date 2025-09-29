<?php namespace App\Http\Controllers;
/**
 * Copyright 2017 OpenStack Foundation
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
use App\Models\Foundation\Marketplace\IApplianceRepository;
use Illuminate\Http\Response;
use models\oauth2\IResourceServerContext;
use OpenApi\Attributes as OA;

/**
 * Class AppliancesApiController
 * @package App\Http\Controllers
 */
final class AppliancesApiController extends AbstractCompanyServiceApiController
{

    /**
     * AppliancesApiController constructor.
     * @param IApplianceRepository $repository
     */
    public function __construct(IApplianceRepository $repository, IResourceServerContext $resource_server_context)
    {
        parent::__construct($repository, $resource_server_context);
    }

    #[OA\Get(
        path: "/api/public/v1/marketplace/appliances",
        description: "Get all marketplace appliances (OpenStack implementations)",
        summary: 'Get all appliances',
        operationId: 'getAllAppliances',
        tags: ['Appliances'],
        parameters: [
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value. Available fields: name, company. Operators: =@, ==, @@.',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'name@@openstack')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'name,-id')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include. Available relations: company, type, capabilities, guests, hypervisors, supported_regions',
                schema: new OA\Schema(type: 'string', example: 'company,type')
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                description: 'Relations to load eagerly',
                schema: new OA\Schema(type: 'string', example: 'company,type')
            ),
            new OA\Parameter(
                name: 'fields',
                in: 'query',
                required: false,
                description: 'Comma-separated list of fields to return',
                schema: new OA\Schema(type: 'string', example: 'id,name,company.name')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success - Returns paginated list of appliances',
                content: new OA\JsonContent(
                    properties: [
                        'total' => new OA\Property(property: 'total', type: 'integer', example: 25),
                        'per_page' => new OA\Property(property: 'per_page', type: 'integer', example: 25),
                        'current_page' => new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        'last_page' => new OA\Property(property: 'last_page', type: 'integer', example: 1),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    'id' => new OA\Property(property: 'id', type: 'integer', example: 1),
                                    'class_name' => new OA\Property(property: 'class_name', type: 'string', example: 'Appliance'),
                                    'name' => new OA\Property(property: 'name', type: 'string', example: 'OpenStack Private Cloud Appliance'),
                                    'overview' => new OA\Property(property: 'overview', type: 'string', example: 'Complete OpenStack solution'),
                                    'call_2_action_url' => new OA\Property(property: 'call_2_action_url', type: 'string', example: 'https://example.com/contact'),
                                    'slug' => new OA\Property(property: 'slug', type: 'string', example: 'openstack-appliance'),
                                    'company_id' => new OA\Property(property: 'company_id', type: 'integer', example: 1),
                                    'type_id' => new OA\Property(property: 'type_id', type: 'integer', example: 1),
                                    'is_compatible_with_storage' => new OA\Property(property: 'is_compatible_with_storage', type: 'boolean', example: true),
                                    'is_compatible_with_compute' => new OA\Property(property: 'is_compatible_with_compute', type: 'boolean', example: true),
                                    'is_compatible_with_federated_identity' => new OA\Property(property: 'is_compatible_with_federated_identity', type: 'boolean', example: false),
                                    'is_compatible_with_platform' => new OA\Property(property: 'is_compatible_with_platform', type: 'boolean', example: true),
                                    'is_openstack_powered' => new OA\Property(property: 'is_openstack_powered', type: 'boolean', example: true),
                                    'is_openstack_tested' => new OA\Property(property: 'is_openstack_tested', type: 'boolean', example: true),
                                    'openstack_tested_info' => new OA\Property(property: 'openstack_tested_info', type: 'string', example: 'Tested with OpenStack Yoga')
                                ],
                                type: 'object'
                            )
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAll()
    {
        return parent::getAll();
    }
}

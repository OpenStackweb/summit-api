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
use App\Models\Foundation\Marketplace\IRemoteCloudServiceRepository;
use Illuminate\Http\Response;
use models\oauth2\IResourceServerContext;
use OpenApi\Attributes as OA;

/**
 * Class RemoteCloudsApiController
 * @package App\Http\Controllers
 */
final class RemoteCloudsApiController extends AbstractCompanyServiceApiController
{
    /**
     * PrivateCloudsApiController constructor.
     * @param IRemoteCloudServiceRepository $repository
     */
    public function __construct(IRemoteCloudServiceRepository $repository, IResourceServerContext $resource_server_context)
    {
        parent::__construct($repository, $resource_server_context);
    }

    #[OA\Get(
        path: "/api/public/v1/marketplace/remotely-managed-private-clouds",
        description: "Get all marketplace remotely managed private cloud services (OpenStack implementations)",
        summary: 'Get all remotely managed private clouds',
        operationId: 'getAllRemotelyManagedPrivateClouds',
        tags: ['Remote Clouds'],
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
                    items: new OA\Items(type: 'string', example: 'name@@managed')
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
                description: 'Success - Returns paginated list of remotely managed private clouds',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedRemoteCloudsResponse')
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

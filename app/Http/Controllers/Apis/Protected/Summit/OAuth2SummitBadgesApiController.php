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
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Security\SummitScopes;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use utils\Filter;
use utils\FilterElement;

/**
 * Class OAuth2SummitBadgesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitBadgesApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    // traits
    use ParametrizedGetAll;

    public function __construct
    (
        ISummitAttendeeBadgeRepository $repository,
        ISummitRepository $summit_repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
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

    // OpenAPI Documentation

    #[OA\Get(
        path: '/api/v1/summits/{id}/badges',
        summary: 'Get all attendee badges for a summit',
        description: 'Retrieves a paginated list of attendee badges for a specific summit. Badges are issued to attendees and contain ticket information, badge type, printing details, and feature assignments (ribbons, special access indicators, etc.).',
        security: [['oauth2_security_scope' => [SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Badges'],
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
                description: 'Filter expressions. Format: field<op>value. Available fields: owner_first_name, owner_last_name, owner_full_name, owner_email, ticket_number, order_number (all support =@, ==). Operators: == (equals), =@ (contains)',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'owner_email==john@example.com')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available fields: id, ticket_number, order_number, created. Use "-" prefix for descending order.',
                schema: new OA\Schema(type: 'string', example: 'created')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships. Available: ticket, type, features',
                schema: new OA\Schema(type: 'string', example: 'ticket,type,features')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attendee badges retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitAttendeeBadgesResponse')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 401, ref: '#/components/responses/401'),
            new OA\Response(response: 403, ref: '#/components/responses/403'),
            new OA\Response(response: 404, ref: '#/components/responses/404'),
            new OA\Response(response: 412, ref: '#/components/responses/412'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ]
    )]

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummit($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function(){
                return [
                    'owner_first_name'           => ['=@', '=='],
                    'owner_last_name'            => ['=@', '=='],
                    'owner_full_name'            => ['=@', '=='],
                    'owner_email'                => ['=@', '=='],
                    'ticket_number'              => ['=@', '=='],
                    'order_number'               => ['=@', '=='],
                ];
            },
            function(){
                return [
                    'owner_first_name'           => 'sometimes|string',
                    'owner_last_name'            => 'sometimes|string',
                    'owner_full_name'            => 'sometimes|string',
                    'owner_email'                => 'sometimes|string',
                    'ticket_number'               => 'sometimes|string',
                    'order_number'                => 'sometimes|string',
                ];
            },
            function()
            {
                return [
                    'id',
                    'ticket_number',
                    'order_number',
                    'created'
                ];
            },
            function($filter) use($summit){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Private;
            }
        );
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/badges/csv',
        summary: 'Export all attendee badges for a summit to CSV',
        description: 'Exports a CSV file containing all attendee badges for a specific summit. Supports the same filtering and ordering capabilities as the standard list endpoint.',
        security: [['oauth2_security_scope' => [SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Badges'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions. Format: field<op>value. Available fields: owner_first_name, owner_last_name, owner_full_name, owner_email, ticket_number, order_number (all support =@, ==)',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'owner_email=@example.com')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available fields: id, ticket_number, order_number, created',
                schema: new OA\Schema(type: 'string', example: '-created')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'CSV file generated successfully',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(
                        type: 'string',
                        format: 'binary'
                    )
                )
            ),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 401, ref: '#/components/responses/401'),
            new OA\Response(response: 403, ref: '#/components/responses/403'),
            new OA\Response(response: 404, ref: '#/components/responses/404'),
            new OA\Response(response: 412, ref: '#/components/responses/412'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ]
    )]

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummitCSV($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function(){
                return [
                    'owner_first_name'           => ['=@', '=='],
                    'owner_last_name'            => ['=@', '=='],
                    'owner_full_name'            => ['=@', '=='],
                    'owner_email'                => ['=@', '=='],
                    'ticket_number'              => ['=@', '=='],
                    'order_number'               => ['=@', '=='],
                ];
            },
            function(){
                return [
                    'owner_first_name'           => 'sometimes|string',
                    'owner_last_name'            => 'sometimes|string',
                    'owner_full_name'            => 'sometimes|string',
                    'owner_email'                => 'sometimes|string',
                    'ticket_number'               => 'sometimes|string',
                    'order_number'                => 'sometimes|string',
                ];
            },
            function()
            {
                return [
                    'id',
                    'ticket_number',
                    'order_number',
                    'created'
                ];
            },
            function($filter) use($summit){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Private;
            },
            function(){
                return [];
            },
            function(){
                return [];
            },
            'attendees-badges-'
        );
    }



}

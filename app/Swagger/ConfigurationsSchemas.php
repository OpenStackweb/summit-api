<?php 
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiScope',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1,
            description: 'Scope ID'
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            example: 'read-all-summit-data',
            description: 'Scope name'
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            example: 'Allows reading all summit data',
            description: 'Scope description'
        ),
        new OA\Property(
            property: 'active',
            type: 'boolean',
            example: true,
            description: 'Whether the scope is active'
        ),
    ]
)]
class ApiScopeSchema {}

#[OA\Schema(
    schema: 'ApiEndpointAuthzGroup',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1,
            description: 'Authorization group ID'
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            example: 'Administrators',
            description: 'Authorization group name'
        ),
    ]
)]
class ApiEndpointAuthzGroupSchema {}

#[OA\Schema(
    schema: 'ApiEndpoint',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1,
            description: 'Endpoint ID'
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            example: 'get-summits',
            description: 'Endpoint name'
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            example: 'Retrieve all summits',
            description: 'Endpoint description'
        ),
        new OA\Property(
            property: 'active',
            type: 'boolean',
            example: true,
            description: 'Whether the endpoint is active'
        ),
        new OA\Property(
            property: 'route',
            type: 'string',
            example: '/api/v1/summits',
            description: 'Endpoint route'
        ),
        new OA\Property(
            property: 'http_method',
            type: 'string',
            example: 'GET',
            description: 'HTTP method',
            enum: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']
        ),
        new OA\Property(
            property: 'scopes',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Required OAuth2 scope IDs'
        ),
        new OA\Property(
            property: 'authz_groups',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Required authorization group IDs'
        ),
    ]
)]
class ApiEndpointSchema {}

#[OA\Schema(
    schema: 'Api',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1,
            description: 'API ID'
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            example: 'summits',
            description: 'API name'
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            example: 'Summit Management API',
            description: 'API description'
        ),
        new OA\Property(
            property: 'active',
            type: 'boolean',
            example: true,
            description: 'Whether the API is active'
        ),
        new OA\Property(
            property: 'scopes',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'API scope IDs'
        ),
        new OA\Property(
            property: 'endpoints',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'API endpoint IDs'
        ),
    ]
)]
class ApiSchema {}

#[OA\Schema(
    schema: 'PublicEndpoint',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'route',
            type: 'string',
            example: '/api/public/v1/timezones',
            description: 'Public endpoint route'
        ),
        new OA\Property(
            property: 'http_methods',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['GET'],
            description: 'Available HTTP methods'
        ),
    ]
)]
class PublicEndpointSchema {}

#[OA\Schema(
    schema: 'EndpointsDefinitionsResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'oauth2_endpoints',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Api'),
            description: 'List of OAuth2 protected APIs with their endpoints'
        ),
        new OA\Property(
            property: 'public_endpoints',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PublicEndpoint'),
            description: 'List of public endpoints'
        ),
    ]
)]
class EndpointsDefinitionsResponseSchema {}

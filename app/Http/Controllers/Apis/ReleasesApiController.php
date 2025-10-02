<?php namespace App\Http\Controllers;
/*
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

use App\Models\Foundation\Software\Repositories\IOpenStackReleaseRepository;
use App\ModelSerializers\SerializerUtils;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;

/**
 * Class ReleasesApiController
 * @package App\Http\Controllers
 */
final class ReleasesApiController extends OAuth2ProtectedController
{
    /**
     * @param IOpenStackReleaseRepository $repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IOpenStackReleaseRepository  $repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
    }

    #[OA\Get(
        path: "/api/v1/releases/current",
        description: "",
        summary: 'Get Current OpenStack Release',
        operationId: 'getCurrentRelease',
        tags: ['Releases'],
        security: [['releases_oauth2' => []]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string', example: 'components,milestones')
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                description: 'Relations to load eagerly',
                schema: new OA\Schema(type: 'string', example: 'components,milestones')
            ),
            new OA\Parameter(
                name: 'fields',
                in: 'query',
                required: false,
                description: 'Comma-separated list of fields to return',
                schema: new OA\Schema(type: 'string', example: 'id,name,version,status')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current OpenStack Release',
                content: new OA\JsonContent(ref: '#/components/schemas/OpenStackRelease')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getCurrent(){
        try{
            $current = $this->repository->getCurrent();
            if (is_null($current)) return $this->error404();
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($current)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        }
        catch (\Exception $ex){
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
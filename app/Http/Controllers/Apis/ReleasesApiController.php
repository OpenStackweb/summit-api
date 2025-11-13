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
use Illuminate\Support\Facades\Log;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

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


    /**
     * @return \Illuminate\Http\JsonResponse|mixed|void
     */
    #[OA\Get(
        path: '/api/v1/releases/current',
        operationId: 'getCurrentRelease',
        description: 'Retrieve the current OpenStack release',
        tags: ['Releases'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Current OpenStack release',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/OpenStackRelease'
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Current release not found'
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Server Error'
            ),
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
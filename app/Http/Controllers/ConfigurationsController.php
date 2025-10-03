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
use App\Models\ResourceServer\IApiRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use Exception;
use OpenApi\Attributes as OA;
/**
 * Class ConfigurationsController
 * @package App\Http\Controllers
 */
final class ConfigurationsController extends JsonController
{
    /**
     * @var IApiRepository
     */
    private $repository;

    /**
     * ConfigurationsController constructor.
     * @param IApiRepository $repository
     */
    public function __construct(IApiRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    #[OA\Get(
        path: "/api/public/v1/configurations/endpoints-definitions",
        summary: "Get OAuth2 and public endpoints definitions",
        description: "Returns the list of OAuth2 endpoints and public endpoints available in the API.",
        operationId: "getEndpointsDefinitions",
        tags: ["Configurations"],
        security: [],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of OAuth2 and public endpoints",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "oauth2_endpoints",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/OAuth2Endpoint")
                        ),
                        new OA\Property(
                            property: "public_endpoints",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/PublicEndpoint")
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error"
            )
        ]
    )]
    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getEndpointsDefinitions(){
        try {
            $items = [];
            foreach ($this->repository->getAll() as $i) {
                if ($i instanceof IEntity) {
                    $i = SerializerRegistry::getInstance()->getSerializer($i, SerializerRegistry::SerializerType_Public)->serialize(Request::input('expand', ''));
                }
                $items[] = $i;
            }

            $routeCollection = Route::getRoutes();

            $public_endpoints = [];
            foreach ($routeCollection as $value) {
                $uri =  $value->uri;
                if(!str_contains($uri, 'api/public/v1')) continue;
                $public_endpoints[] = [
                    'route' => $uri,
                    'http_methods' =>  $value->methods,
                ];
            }

            return $this->ok(
                [
                    'oauth2_endpoints' => $items,
                    'public_endpoints' => $public_endpoints,
                ]
            );
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
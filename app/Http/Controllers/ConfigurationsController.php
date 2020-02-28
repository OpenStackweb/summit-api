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
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use Exception;
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

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getEndpointsDefinitions(){
        try {
            $items = [];
            foreach ($this->repository->getAll() as $i) {
                if ($i instanceof IEntity) {
                    $i = SerializerRegistry::getInstance()->getSerializer($i, SerializerRegistry::SerializerType_Public)->serialize(Input::get('expand', ''));
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
<?php namespace App\Http\Middleware;
/**
 * Copyright 2016 OpenStack Foundation
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

use App\Models\ResourceServer\ApiEndpoint;
use App\Models\ResourceServer\IApiEndpointRepository;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use libs\utils\RequestUtils;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
/**
 * Class UserAuthEndpoint
 * @package App\Http\Middleware
 */
final class UserAuthEndpoint
{

    /**
     * @var IResourceServerContext
     */
    private $context;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var IApiEndpointRepository
     */
    private $endpoint_repository;

    /**
     * UserAuthEndpoint constructor.
     * @param IResourceServerContext $context
     * @param IMemberRepository $member_repository
     * @param IApiEndpointRepository $endpoint_repository
     */
    public function __construct
    (
        IResourceServerContext $context,
        IMemberRepository $member_repository,
        IApiEndpointRepository $endpoint_repository
    )
    {
        $this->context           = $context;
        $this->member_repository = $member_repository;
        $this->endpoint_repository  = $endpoint_repository;
    }

    /**
     * @param $request
     * @param Closure $next
     * @param $required_groups
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function handle($request, Closure $next)
    {
        $current_member = $this->context->getCurrentUser();
        if (is_null($current_member)) return $next($request);
        $method = $request->getMethod();
        $route = RequestUtils::getCurrentRoutePath($request);

        $key_auth = sprintf("user_auth_endpoint_%s_%s_%s", $route, $method, $current_member->getId());

        if(Cache::has($key_auth)){
            $res_auth = Cache::get($key_auth, null);
            if(!is_null($res_auth)) {
                if ($res_auth == 1) {
                    Log::debug(sprintf("UserAuthEndpoint::handle cache hit for key %s member %s is authorized", $key_auth, $current_member->getId()));
                    return $next($request);
                } else if ($res_auth == 0) {
                    Log::warning(sprintf("UserAuthEndpoint::handle member %s is not authorized", $current_member->getId()));
                    $http_response = Response::json(['error' => 'unauthorized member'], 403);
                    return $http_response;
                }
            }
        }

        Log::debug(sprintf("UserAuthEndpoint::handle cache miss for key %s", $key_auth));

        $required_groups = [];
        $key = sprintf("user_auth_endpoint_%s_%s", $route, $method);
        if(Cache::has($key)){
            Log::debug(sprintf("UserAuthEndpoint::handle cache hit for key %s", $key));
            $res = Cache::get($key);
            if(!empty($res)){
                $required_groups = json_decode(gzinflate($res),false);
            }
        }
        else {
            Log::debug(sprintf("UserAuthEndpoint::handle cache miss for key %s", $key));
            $endpoint = $this->endpoint_repository->getApiEndpointByUrlAndMethod($route, $method);
            if (is_null($endpoint)) return $next($request);
            if (!$endpoint instanceof ApiEndpoint) return $next($request);
            $required_groups = [];
            foreach($endpoint->getAuthzGroups() as $authzGroup){
                $required_groups[] = $authzGroup->getSlug();
            }
            Cache::add($key, gzdeflate(json_encode($required_groups), 9));
        }

        Log::debug
        (
            sprintf
            (
                "UserAuthEndpoint::handle route %s method %s member %s (%s) required groups %s",
                $route,
                $method,
                $current_member->getId(),
                $current_member->getEmail(),
                json_encode($required_groups)
            )
        );

        foreach ($required_groups as $required_group_slug) {
            if($current_member->isOnGroup($required_group_slug)) {
                Log::debug
                (
                    sprintf
                    (
                        "UserAuthEndpoint::handle member %s is on group %s request %s is authorized",
                        $current_member->getId(),
                        $required_group_slug,
                        $request->path()
                    )
                );
                Cache::add($key_auth, 1);
                return $next($request);
            }
        }

        Log::warning(sprintf("UserAuthEndpoint::handle member %s is not authorized", $current_member->getId()));
        Cache::add($key_auth, 0);
        $http_response = Response::json(['error' => 'unauthorized member'], 403);
        return $http_response;
    }
}
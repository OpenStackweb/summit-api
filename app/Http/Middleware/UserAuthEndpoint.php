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
        $endpoint = $this->endpoint_repository->getApiEndpointByUrlAndMethod($route, $method);
        if(is_null($endpoint)) return $next($request);
        if(!$endpoint instanceof ApiEndpoint) return $next($request);
        $required_groups = $endpoint->getAuthzGroups();

        foreach ($required_groups as $required_group) {
            Log::debug(sprintf("UserAuthEndpoint::handle route %s method %s member %s (%s) required group %s",
                $route, $method, $current_member->getId(), $current_member->getEmail(), $required_group->getSlug()));

            if($current_member->isOnGroup($required_group->getSlug())) {
                Log::debug(sprintf("UserAuthEndpoint::handle member %s is on group %s request %s", $current_member->getId(), $required_group->getSlug(), $request->path()));
                return $next($request);
            }
        }

        Log::warning(sprintf("UserAuthEndpoint::handle member %s is not authorized", $current_member->getId()));
        $http_response = Response::json(['error' => 'unauthorized member'], 403);
        return $http_response;
    }
}
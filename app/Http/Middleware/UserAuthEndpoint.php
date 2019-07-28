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
use Closure;
use Illuminate\Support\Facades\Response;
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
     * UserAuthEndpoint constructor.
     * @param IResourceServerContext $context
     * @param IMemberRepository $member_repository
     */
    public function __construct
    (
        IResourceServerContext $context,
        IMemberRepository $member_repository
    )
    {
        $this->context           = $context;
        $this->member_repository = $member_repository;
    }

    /**
     * @param $request
     * @param Closure $next
     * @param $required_groups
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function handle($request, Closure $next, $required_groups)
    {

        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) return $next($request);

        $required_groups = explode('|', $required_groups);

        foreach ($required_groups as $required_group) {
            if($current_member->isOnGroup($required_group))
                return $next($request);
        }

        $http_response = Response::json(['error' => 'unauthorized member'], 403);
        return $http_response;
    }
}
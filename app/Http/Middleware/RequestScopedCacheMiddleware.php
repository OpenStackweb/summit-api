<?php namespace App\Http\Middleware;
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

use App\ModelSerializers\Traits\RequestScopedCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Closure;
/**
 * Class RequestScopedCacheMiddleware
 * @package App\Http\Middleware
 */
final class RequestScopedCacheMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @param $cache_lifetime
     * @return JsonResponse
     */
    public function handle($request, Closure $next)
    {
        $scope = RequestScopedCache::getScopeId();

        Log::debug(sprintf('RequestScopedCacheMiddleware::handle scope %s', $scope));
        if ($request->getMethod() !== 'GET') {
            // short circuit
            Log::debug('RequestScopedCacheMiddleware::handle method is not GET');
            return $next($request);
        }

        $response = $next($request);

        // clear all related to current session
        Log::debug(sprintf( 'RequestScopedCacheMiddleware::handle clearing cache scope %s', $scope));
        Cache::tags($scope)->flush();
        return $response;
    }
}
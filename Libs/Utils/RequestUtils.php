<?php namespace libs\utils;
/**
 * Copyright 2015 OpenStack Foundation
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use libs\oauth2\OAuth2Protocol;
use libs\oauth2\OAuth2ResourceServerException;
use URL\Normalizer;

/**
 * Class RequestUtils
 * @package libs\utils
 */
final class RequestUtils {

    /**
     * @param Request $request
     * @return bool|string
     */
    public static function getCurrentRoutePath($request)
    {
        try
        {
            $route = Route::getRoutes()->match($request);
            if(is_null($route)) return false;
            $route_path  = $route->uri();
            if (strpos($route_path, '/') != 0)
                $route_path = '/' . $route_path;

            return $route_path;
        }
        catch (\Exception $ex)
        {
            Log::error($ex);
        }
        return false;
    }

    /**
     * @param Request $request
     * @return string|null
     * @throws OAuth2ResourceServerException
     */
    public static function getOrigin(Request $request): ?string
    {
        // http://tools.ietf.org/id/draft-abarth-origin-03.html
        $origin = $request->headers->get('Origin');
        $referer = $request->headers->get('Referer');

        if (!empty($origin) && !empty($referer) &&
            parse_url($origin, PHP_URL_HOST) != parse_url($referer, PHP_URL_HOST))
        {
            Log::warning('OAuth2BearerAccessTokenRequestValidator::handle Origin and Referrer mismatch');
            throw new OAuth2ResourceServerException(
                403,
                OAuth2Protocol::OAuth2Protocol_Error_InvalidRequest,
                'Origin and Referrer mismatch'
            );
        }

        if (empty($origin) && !empty($referer)) {
            $referer_parts = parse_url($referer);
            $origin = $referer_parts['scheme'] . '://' . $referer_parts['host'];
            if (!empty($origin)) {
                Log::warning('OAuth2BearerAccessTokenRequestValidator::Origin header not present. Using normalized Referer as fallback: ' . $origin);
            }
        }

        if (!empty($origin)) {
            $origin = (new Normalizer($origin))->normalize();
        }
        return $origin;
    }
}
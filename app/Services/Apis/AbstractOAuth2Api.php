<?php namespace App\Services\Apis;
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
use App\Services\Auth\OAuth2ClientFactory;;
use Illuminate\Support\Facades\Config;
use libs\utils\ICacheService;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\GenericProvider;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
/**
 * Class AbstractOAuth2Api
 * @package App\Services\Apis
 */
abstract class AbstractOAuth2Api
{
    const MaxRetries = 3;

    /**
     * @var ICacheService
     */
    protected $cacheService;

    /**
     * ExternalUserApi constructor.
     * @param ICacheService $cacheService
     */
    public function __construct(ICacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    const SkewTime            = 120;
    const AccessTokenCacheKey = '%s_OAUTH2_ACCESS_TOKEN';

    public abstract function getAppName():string;

    public function getIdpConfig():array {
        return [
            'authorization_endpoint' => Config::get("idp.authorization_endpoint"),
            'token_endpoint'         => Config::get("idp.token_endpoint"),
        ];
    }

    /**
     * @return array
     */
    public abstract function getAppConfig():array;

    /**
     * @return GenericProvider
     */
    private function getIDPClient():GenericProvider {
        return OAuth2ClientFactory::build
        (
            $this->getIdpConfig(),
            $this->getAppConfig()
        );
    }

    /**
     * @return string
     */
    private function getAccessTokenCacheKey():string{
        return sprintf(self::AccessTokenCacheKey, $this->getAppName());
    }

    /**
     * @param \Closure $callback
     * @return mixed|null
     * @throws \Exception
     */
    protected function invokeWithRetry(\Closure $callback)
    {
        $retry = 0;
        $done = false;
        $result = null;

        while (!$done and $retry < self::MaxRetries) {
            try {
                $result = $callback($this);
                $done = true;
            } catch (ClientException $ex) {
                Log::warning("AbstractOAuth2Api::invokeWithRetry retrying ...");
                $retry++;
                if ($retry === self::MaxRetries) {
                    if ($ex->getCode() == 404) {
                        return null;
                    }
                    throw $ex;
                }
                $this->cleanAccessToken();
                Log::warning($ex);
            }
            catch (RequestException $ex){
                $this->cleanAccessToken();
                Log::error($ex);
                if($ex->getCode() == 404){
                    return null;
                }
                throw $ex;
            }
            catch (\Exception $ex) {
                $this->cleanAccessToken();
                Log::error($ex);
                throw $ex;
            }
        }
        return $result;
    }


        /**
     * @return string|null
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    protected function getAccessToken():?string{
        Log::debug("AbstractOAuth2Api::getAccessToken");
        $token = $this->cacheService->getSingleValue($this->getAccessTokenCacheKey());
        if (empty($token)) {
            try {
                Log::debug("AbstractOAuth2Api::getAccessToken - access token is empty, getting new one");
                $client    = $this->getIDPClient();
                $appConfig = $this->getAppConfig();
                $scopes    = $appConfig['scopes'] ?? [];
                Log::debug(sprintf( "AbstractOAuth2Api::getAccessToken - got scopes %s", $scopes));
                // Try to get an access token using the client credentials grant.
                $accessToken = $client->getAccessToken('client_credentials', ['scope' => $scopes]);
                $token = $accessToken->getToken();
                $expires_in = $accessToken->getExpires() - time();
                Log::debug(sprintf("AbstractOAuth2Api::getAccessToken  - setting new access token %s expires in %s", $token, $expires_in));
                $ttl = $expires_in - self::SkewTime;
                if($ttl < 0)
                    $ttl = $expires_in;
                if($ttl > 0) {
                    Log::debug(sprintf("AbstractOAuth2Api::getAccessToken ttl %s", $ttl));
                    $this->cacheService->setSingleValue($this->getAccessTokenCacheKey(), $token, $ttl);
                }
            }
            catch (ClientException $ex){
                Log::warning($ex);
                if($ex->getCode() == 401) {
                    // invalid token
                    $this->cacheService->delete($this->getAccessTokenCacheKey());
                }
                throw $ex;
            }
        }
        Log::debug(sprintf("AbstractOAuth2Api::getAccessToken - returning access token %s", $token));
        return $token;
    }

    protected function cleanAccessToken():void{
        $this->cacheService->delete($this->getAccessTokenCacheKey());
    }
}
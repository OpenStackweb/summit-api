<?php namespace App\Models\ResourceServer;
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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;
use libs\oauth2\InvalidGrantTypeException;
use libs\oauth2\OAuth2InvalidIntrospectionResponse;
use libs\oauth2\OAuth2Protocol;
use libs\utils\ConfigurationException;
use libs\utils\ICacheService;
use models\oauth2\AccessToken;
use Illuminate\Support\Facades\Log;
/**
 * Class AccessTokenService
 * @package App\Models\ResourceServer
 */
final class AccessTokenService implements IAccessTokenService
{

    static $access_token_keys = [
        'access_token',
        'scope',
        'client_id',
        'audience',
        'expires_in',
        'application_type',
        'allowed_return_uris',
        'allowed_origins',
        'user_external_id',
        'user_identifier',
        'user_id',
        'user_email',
        'user_first_name',
        'user_last_name',
        'user_groups',
    ];

    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * @param ICacheService $cache_service
     */
    public function __construct(ICacheService $cache_service)
    {
        $this->cache_service = $cache_service;
    }

    /**
     * @param string $token_value
     * @return AccessToken
     * @throws \Exception
     */
    public function get($token_value)
    {
        $token          = null;
        $cache_lifetime = intval(Config::get('server.access_token_cache_lifetime', 300));

        if($this->cache_service->exists(md5($token_value).'.revoked'))
        {
            Log::debug(sprintf('token marked as revoked on cache (%s)',md5($token_value) ));
            throw new InvalidGrantTypeException(OAuth2Protocol::OAuth2Protocol_Error_InvalidToken);
        }

        $token_info = $this->cache_service->getHash(md5($token_value), self::$access_token_keys);

        if (count($token_info) === 0)
        {
            $token_info = $this->doIntrospection($token_value);
        }
        else
        {
            $cache_remaining_lifetime = intval($this->cache_service->ttl(md5($token_value)));
            $expires_in               = intval($token_info['expires_in']);
            $token_info['expires_in'] = $expires_in - ( $cache_lifetime - $cache_remaining_lifetime);
            Log::debug
            (
                sprintf
                (
                    "original token life time %s - current token life time %s - token cache remaining lifetime %s",
                    $expires_in,
                    $token_info['expires_in'],
                    $cache_remaining_lifetime
                )
            );
        }

        $token = $this->unSerializeToken($token_info);

        if($token->getLifetime() <= 0)
        {
            Log::debug("token lifetime is <= 0 ... retrieving from IDP");
            $this->cache_service->delete(md5($token_value));
            $token_info = $this->doIntrospection($token_value);
            $token      = $this->unSerializeToken($token_info);
        }
        return $token;
    }

    /**
     * @param array $token_info
     * @return AccessToken
     */
    private function unSerializeToken(array $token_info){

        $token = AccessToken::createFromParams($token_info);

        $str_token_info = "";
        foreach($token_info as $k => $v){
            $str_token_info  .= sprintf("-%s=%s-", $k, $v);
        }

        Log::debug("token info : ". $str_token_info);

        return $token;
    }
    /**
     * @param string $token_value
     * @return array
     */
    private function doIntrospection($token_value){
        Log::debug("getting token from remote call ...");
        $cache_lifetime = intval(Config::get('server.access_token_cache_lifetime', 300));
        $token_info     = $this->doIntrospectionRequest($token_value);

        // legacy fix
        if(!array_key_exists("user_external_id" , $token_info)){
            $token_info['user_external_id'] = null;
        }

        if(!array_key_exists("user_identifier" , $token_info)){
            $token_info['user_identifier'] = null;
        }

        if(!array_key_exists("user_email" , $token_info)){
            $token_info['user_email'] = null;
        }

        if(!array_key_exists("user_first_name" , $token_info)){
            $token_info['user_first_name'] = null;
        }

        if(!array_key_exists("user_last_name" , $token_info)){
            $token_info['user_last_name'] = null;
        }

        if(array_key_exists("user_groups" , $token_info)){
           $token_info['user_groups'] = json_encode($token_info['user_groups']);
        }

        $this->cache_service->storeHash(md5($token_value), $token_info, $cache_lifetime);
        return $token_info;
    }

    /**
     * @param $token_value
     * @return mixed
     * @throws ConfigurationException
     * @throws InvalidGrantTypeException
     * @throws OAuth2InvalidIntrospectionResponse
     * @throws \Exception
     */
    private function doIntrospectionRequest($token_value)
    {

        try {
            $client = new Client([
                'timeout'         => Config::get('curl.timeout', 60),
                'allow_redirects' => Config::get('curl.allow_redirects', false),
                'verify'          => Config::get('curl.verify_ssl_cert', true)
            ]);

            $client_id       = Config::get('app.openstackid_client_id', '');
            $client_secret   = Config::get('app.openstackid_client_secret', '');
            $auth_server_url = Config::get('app.openstackid_base_url', '');

            if (empty($client_id)) {
                throw new ConfigurationException('app.openstackid_client_id param is missing!');
            }

            if (empty($client_secret)) {
                throw new ConfigurationException('app.openstackid_client_secret param is missing!');
            }

            if (empty($auth_server_url)) {
                throw new ConfigurationException('app.openstackid_base_url param is missing!');
            }
            // http://docs.guzzlephp.org/en/stable/request-options.html
            $response = $client->request('POST',
                  "{$auth_server_url}/oauth2/token/introspection",
                [
                    'form_params'  => ['token' => $token_value],
                    'auth'         => [$client_id, $client_secret],
                    'timeout'      => 120,
                    'http_errors' => true
                ]
            );

            $content_type = $response->getHeaderLine('content-type');
            if(!str_contains($content_type, 'application/json'))
            {
                // invalid content type
                throw new \Exception($response->getBody());
            }
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (RequestException $ex) {

            Log::warning($ex->getMessage());
            $response  = $ex->getResponse();

            if(is_null($response))
                throw new OAuth2InvalidIntrospectionResponse(sprintf('http code %s', $ex->getCode()));

            $content_type = $response->getHeaderLine('content-type');
            $is_json      = str_contains($content_type, 'application/json');
            $body         = $response->getBody()->getContents();
            $body         = $is_json ? json_decode($body, true): $body;
            $code         = $response->getStatusCode();

            if ($code === 400 && $is_json && isset($body['error'])
                && (
                    $body['error'] === OAuth2Protocol::OAuth2Protocol_Error_InvalidToken ||
                    $body['error'] === OAuth2Protocol::OAuth2Protocol_Error_InvalidGrant
                ))
            {
                $this->cache_service->setSingleValue(md5($token_value).'.revoked', md5($token_value));
                throw new InvalidGrantTypeException($body['error']);
            }
            if($code == 503 ){
                // service went online temporally ... revoke token
                $this->cache_service->setSingleValue(md5($token_value).'.revoked', md5($token_value));
                throw new InvalidGrantTypeException(OAuth2Protocol::OAuth2Protocol_Error_InvalidToken);
            }
            throw new OAuth2InvalidIntrospectionResponse(sprintf('http code %s - body %s', $ex->getCode(), $body));
        }
    }
}
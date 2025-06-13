<?php namespace App\Http\Middleware;
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
use App\Models\ResourceServer\IAccessTokenService;
use App\Models\ResourceServer\IApiEndpointRepository;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use libs\oauth2\BearerAccessTokenAuthorizationHeaderParser;
use libs\oauth2\InvalidGrantTypeException;
use libs\oauth2\OAuth2Protocol;
use libs\oauth2\OAuth2ResourceServerException;
use libs\oauth2\OAuth2WWWAuthenticateErrorResponse;
use libs\utils\RequestUtils;
use models\oauth2\IResourceServerContext;
use URL\Normalizer;

/**
 * Class OAuth2BearerAccessTokenRequestValidator
 * http://tools.ietf.org/html/rfc6749#section-7
 * @package App\Http\Middleware
 */
class OAuth2BearerAccessTokenRequestValidator
{

    /**
     * @var IResourceServerContext
     */
    private $context;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var IApiEndpointRepository
     */
    private $endpoint_repository;

    /**
     * @var IAccessTokenService
     */
    private $token_service;

    /**
     * @param IResourceServerContext $context
     * @param IApiEndpointRepository $endpoint_repository
     * @param IAccessTokenService $token_service
     */
    public function __construct(
        IResourceServerContext $context,
        IApiEndpointRepository $endpoint_repository,
        IAccessTokenService $token_service
    ) {
        $this->context             = $context;
        $this->headers             = $this->getHeaders();
        $this->endpoint_repository = $endpoint_repository;
        $this->token_service       = $token_service;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return OAuth2WWWAuthenticateErrorResponse
     */
    public function handle($request, Closure $next)
    {
        $url    = $request->getRequestUri();
        $method = $request->getMethod();
        $realm  = $request->getHost();

        Log::debug(sprintf("OAuth2BearerAccessTokenRequestValidator::handle url %s method %s", $url, $method));

        try {

            $route = RequestUtils::getCurrentRoutePath($request);
            if (!$route) {
                Log::warning
                (
                    sprintf
                    (
                        'OAuth2BearerAccessTokenRequestValidator::handle API endpoint does not exists! (%s:%s)'
                        , $url,
                        $method
                    )
                );

                throw new OAuth2ResourceServerException(
                    400,
                    OAuth2Protocol::OAuth2Protocol_Error_InvalidRequest,
                    sprintf('API endpoint does not exists! (%s:%s)', $url, $method)
                );
            }

            Log::debug($request->headers->__toString());

            $origin = RequestUtils::getOrigin($request);

            //check first http basic auth header
            $auth_header = isset($this->headers['authorization']) ? $this->headers['authorization'] : null;
            if (!is_null($auth_header) && !empty($auth_header)) {
                $access_token_value = BearerAccessTokenAuthorizationHeaderParser::getInstance()->parse($auth_header);
            } else {
                // http://tools.ietf.org/html/rfc6750#section-2- 2
                // if access token is not on authorization header check on POST/GET params
                $access_token_value = Request::input(OAuth2Protocol::OAuth2Protocol_AccessToken, '');
            }

            if (is_null($access_token_value) || empty($access_token_value)) {
                Log::warning
                (
                    'OAuth2BearerAccessTokenRequestValidator::handle missing access token'
                );
                //if access token value is not set, then error
                throw new OAuth2ResourceServerException(
                    400,
                    OAuth2Protocol::OAuth2Protocol_Error_InvalidRequest,
                    'missing access token'
                );
            }

            $endpoint = $this->endpoint_repository->getApiEndpointByUrlAndMethod($route, $method);

            //api endpoint must be registered on db and active
            if (is_null($endpoint) || !$endpoint->isActive()) {
                Log::warning
                (
                    sprintf('OAuth2BearerAccessTokenRequestValidator::handle API endpoint does not exits! (%s:%s)', $route, $method)
                );
                throw new OAuth2ResourceServerException(
                    400,
                    OAuth2Protocol::OAuth2Protocol_Error_InvalidRequest,
                    sprintf('API endpoint does not exits! (%s:%s)', $route, $method)
                );
            }

            $token_info = $this->token_service->get($access_token_value);

            if(!is_null($token_info))
                Log::debug(sprintf("OAuth2BearerAccessTokenRequestValidator::handle token lifetime %s", $token_info->getLifetime()));

            //check lifetime
            if (is_null($token_info)) {
                Log::warning("OAuth2BearerAccessTokenRequestValidator::handle token not found");
                throw new InvalidGrantTypeException(OAuth2Protocol::OAuth2Protocol_Error_InvalidToken);
            }
            //check token audience
            Log::debug('OAuth2BearerAccessTokenRequestValidator::handle checking token audience ...');
            $audience = explode(' ', $token_info->getAudience());
            if ((!in_array($realm, $audience))) {
                Log::warning
                (
                    sprintf
                    (
                        "OAuth2BearerAccessTokenRequestValidator::handle invalid audience %s current aud %s",
                        $realm,
                        json_encode($audience)
                    )
                );
                throw new InvalidGrantTypeException(OAuth2Protocol::OAuth2Protocol_Error_InvalidToken);
            }
            if (
                $token_info->getApplicationType() === 'JS_CLIENT'
                && (is_null($origin) || empty($origin)|| str_contains($token_info->getAllowedOrigins(), $origin) === false )
            ) {
                //check origins
                throw new OAuth2ResourceServerException(
                    403,
                    OAuth2Protocol::OAuth2Protocol_Error_UnauthorizedClient,
                    sprintf('invalid origin %s - allowed ones (%s)', $origin, $token_info->getAllowedOrigins())
                );
            }
            //check scopes
            Log::debug('OAuth2BearerAccessTokenRequestValidator::handle checking token scopes ...');
            $endpoint_scopes = $endpoint->getScopesNames();
            Log::debug(sprintf("OAuth2BearerAccessTokenRequestValidator::handle endpoint scopes %s", implode(' ',$endpoint_scopes)));
            Log::debug(sprintf("OAuth2BearerAccessTokenRequestValidator::handle token scopes %s", $token_info->getScope()));
            $token_scopes = explode(' ', $token_info->getScope());

            //check token available scopes vs. endpoint scopes
            if (count(array_intersect($endpoint_scopes, $token_scopes)) == 0) {
                Log::warning(
                    sprintf(
                        'OAuth2BearerAccessTokenRequestValidator::handle access token scopes (%s) does not allow to access to api url %s , needed scopes %s',
                        $token_info->getScope(),
                        $url,
                        implode(' OR ', $endpoint_scopes)
                    )
                );

                throw new OAuth2ResourceServerException(
                    403,
                    OAuth2Protocol::OAuth2Protocol_Error_InsufficientScope,
                    'the request requires higher privileges than provided by the access token',
                    implode(' ', $endpoint_scopes)
                );
            }
            Log::debug('OAuth2BearerAccessTokenRequestValidator::handle setting resource server context ...');
            //set context for api and continue processing
            $context = [
                'access_token'        => $access_token_value,
                'expires_in'          => $token_info->getLifetime(),
                'client_id'           => $token_info->getClientId(),
                'scope'               => $token_info->getScope(),
                'application_type'    => $token_info->getApplicationType(),
                'allowed_origins'     => $token_info->getAllowedOrigins(),
                'allowed_return_uris' => $token_info->getAllowedReturnUris()
            ];

            if (!is_null($token_info->getUserId()))
            {
                Log::debug(sprintf("OAuth2BearerAccessTokenRequestValidator::handle user id is not null (%s)", $token_info->getUserId()));
                $context['user_id']          = $token_info->getUserId();
                $context['user_external_id'] = $token_info->getUserExternalId();
                $context['user_identifier']  = $token_info->getUserIdentifier();
                $context['user_email']       = $token_info->getUserEmail();
                $context['user_email_verified'] = $token_info->isUserEmailVerified();
                $context['user_first_name']  = $token_info->getUserFirstName();
                $context['user_last_name']   = $token_info->getUserLastName();
                $context['user_groups']      = $token_info->getUserGroups();
            }

            $this->context->setAuthorizationContext($context);

        }
        catch (OAuth2ResourceServerException $ex1)
        {
            Log::warning($ex1);
            $response = new OAuth2WWWAuthenticateErrorResponse(
                $realm,
                $ex1->getError(),
                $ex1->getErrorDescription(),
                $ex1->getScope(),
                $ex1->getHttpCode()
            );
            $http_response = Response::json($response->getContent(), $response->getHttpCode());
            $http_response->header('WWW-Authenticate', $response->getWWWAuthenticateHeaderValue());

            return $http_response;
        }
        catch (InvalidGrantTypeException $ex2)
        {
            Log::warning($ex2);
            $response = new OAuth2WWWAuthenticateErrorResponse(
                $realm,
                OAuth2Protocol::OAuth2Protocol_Error_InvalidToken,
                'The access token provided is expired, revoked, malformed, or invalid for other reasons.',
                null,
                401
            );
            $http_response = Response::json($response->getContent(), $response->getHttpCode());
            $http_response->header('WWW-Authenticate', $response->getWWWAuthenticateHeaderValue());

            return $http_response;
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = new OAuth2WWWAuthenticateErrorResponse(
                $realm,
                OAuth2Protocol::OAuth2Protocol_Error_InvalidRequest,
                'invalid request',
                null,
                400
            );
            $http_response = Response::json($response->getContent(), $response->getHttpCode());
            $http_response->header('WWW-Authenticate', $response->getWWWAuthenticateHeaderValue());

            return $http_response;
        }
        $response = $next($request);

        return $response;
    }

    /**
     * @return array
     */
    protected function getHeaders():array
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[strtolower($name)] = $value;
            }
        }

        if(!isset($this->headers['authorization'])) {

            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[strtolower($name)] = $value;
                }
            }
            foreach (Request::header() as $name => $value) {
                if (!array_key_exists($name, $headers)) {
                    $headers[strtolower($name)] = $value[0];
                }
            }
        }

        return $headers;
    }
}
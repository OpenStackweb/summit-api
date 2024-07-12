<?php namespace App\Services\Auth;
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
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\GenericProvider;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
/**
 * Class OAuth2ClientFactory
 * @package App\Services\Auth
 */
final class OAuth2ClientFactory {
  public static function build(
    array $idpConfig,
    array $appConfig,
    string $redirectUri = "",
  ): GenericProvider {
    $client_id = $appConfig["client_id"] ?? "";
    $client_secret = $appConfig["client_secret"] ?? "";
    $scopes = $appConfig["scopes"] ?? "";

    Log::debug(
      sprintf(
        "OAuth2ClientFactory::build client_id %s client_secret %s scopes %s",
        $client_id,
        $client_secret,
        $scopes,
      ),
    );

    $provider = new GenericProvider([
      "clientId" => $client_id,
      "clientSecret" => $client_secret,
      "redirectUri" => $redirectUri,
      "urlAuthorize" => $idpConfig["authorization_endpoint"] ?? "",
      "urlAccessToken" => $idpConfig["token_endpoint"] ?? "",
      "urlResourceOwnerDetails" => "",
      "scopes" => $scopes,
    ]);

    $stack = HandlerStack::create();
    $stack->push(GuzzleRetryMiddleware::factory());

    $provider->setHttpClient(
      new Client([
        "handler" => $stack,
        "timeout" => Config::get("curl.timeout", 60),
        "allow_redirects" => Config::get("curl.allow_redirects", false),
        "verify" => Config::get("curl.verify_ssl_cert", true),
      ]),
    );
    return $provider;
  }
}

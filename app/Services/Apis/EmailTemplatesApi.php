<?php namespace App\Services\Apis;
/**
 * Copyright 2023 OpenStack Foundation
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
use GuzzleHttp\Exception\RequestException;
use models\exceptions\ValidationException;
use libs\utils\ICacheService;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Exception;
/**
 * Class EmailTemplatesApi
 * @package App\Services
 */
final class EmailTemplatesApi extends AbstractOAuth2Api
    implements IEmailTemplatesApi
{
    const AppName = 'EMAIL_TEMPLATES_SERVICE';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $scopes;

    /**
     * ExternalUserApi constructor.
     * @param ICacheService $cacheService
     */
    public function __construct(ICacheService $cacheService)
    {
        parent::__construct($cacheService);
        $stack = HandlerStack::create();
        $stack->push(GuzzleRetryMiddleware::factory());

        $this->client = new Client([
            'handler'         => $stack,
            'base_uri'        => Config::get('mail.service_base_url') ?? '',
            'timeout'         => Config::get('curl.timeout', 60),
            'allow_redirects' => Config::get('curl.allow_redirects', false),
            'verify'          => Config::get('curl.verify_ssl_cert', true),
        ]);
    }

    /**
     * @return string
     */
    public function getAppName(): string
    {
        return self::AppName;
    }

    /**
     * @return array
     */
    public function getAppConfig(): array
    {
        return [
            'client_id' => Config::get("mail.service_client_id"),
            'client_secret' => Config::get("mail.service_client_secret"),
            'scopes' => Config::get("mail.service_client_scopes")
        ];
    }

    /**
     * @param string $template_id
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function getEmailTemplate(string $template_id) {
        Log::debug("EmailTemplatesApi::getEmailTemplate");

        try {
            $query = [
                'access_token' => $this->getAccessToken()
            ];

            $response = $this->client->get("/api/v1/mail-templates/{$template_id}", [
                    'query' => $query,
                ]
            );
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (Exception $ex) {
            $this->cleanAccessToken();
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param array $payload
     * @param string $html_template
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException|\League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function getEmailPreview(array $payload, string $html_template)
    {
        Log::debug("EmailTemplatesApi::getEmailPreview");

        try {
            $query = [
                'access_token' => $this->getAccessToken()
            ];

            $response = $this->client->put('/api/v1/mail-templates/all/render', [
                    'query' => $query,
                    RequestOptions::JSON => [
                        "html"    => $html_template,
                        "payload" => $payload
                    ]
                ]
            );
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (Exception $ex) {
            $this->cleanAccessToken();
            Log::error($ex);
            throw $ex;
        }
    }
}


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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Keepsuit\LaravelOpenTelemetry\Support\HttpClient\GuzzleTraceMiddleware;
use libs\utils\ICacheService;
use models\exceptions\ValidationException;
/**
 * Class MailApi
 * @package App\Services\Apis
 */
final class MailApi extends AbstractOAuth2Api implements IMailApi
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $scopes;


    const AppName = 'MAIL_SERVICE';

    /**
     * @return string
     */
    public function getAppName(): string
    {
        return self::AppName;
    }

    /**
     * ExternalUserApi constructor.
     * @param ICacheService $cacheService
     */
    public function __construct(ICacheService $cacheService)
    {
        parent::__construct($cacheService);
        $stack = HandlerStack::create();
        $stack->push(GuzzleRetryMiddleware::factory());
        if (config('opentelemetry.enabled', false))
            $stack->push(GuzzleTraceMiddleware::make());

        $this->client = new Client([
            'handler'         => $stack,
            'base_uri'        => Config::get('mail.service_base_url') ?? '',
            'timeout'         => Config::get('curl.timeout', 60),
            'allow_redirects' => Config::get('curl.allow_redirects', false),
            'verify'          => Config::get('curl.verify_ssl_cert', true),
        ]);
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
     * @param array $payload
     * @param string $template_identifier
     * @param string $to_email
     * @param string|null $subject
     * @param string|null $cc_email
     * @param string|null $bbc_email
     * @return array
     * @throws ValidationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function sendEmail
    (
        array $payload,
        string $template_identifier,
        string $to_email,
        string $subject = null,
        string $cc_email = null ,
        string $bbc_email = null
    ): array
    {

        Log::debug
        (
            sprintf
            (
                "MailApi::sendEmail template_identifier %s to_email %s payload %s",
                $template_identifier,
                $to_email,
                json_encode($payload)
            )
        );

        try {
            if(empty($to_email))
                throw new ValidationException("to_email field es required.");

            if(empty($template_identifier))
                throw new ValidationException("template_identifier field es required.");

            $query = [
                'access_token' => $this->getAccessToken()
            ];

            $payload = [
                'payload' => $payload,
                'template' => $template_identifier,
                'to_email' => $to_email
            ];

            if(!empty($cc_email)){
                Log::debug(sprintf("MailApi::sendEmail setting cc_email %s", $cc_email));
                $payload['cc_email'] = trim($cc_email);
            }

            if(!empty($bcc_email)){
                Log::debug(sprintf("MailApi::sendEmail setting bcc_email %s", $bcc_email));
                $payload['bcc_email'] = trim($bcc_email);
            }

            $response = $this->client->post('/api/v1/mails', [
                    'query' => $query,
                    RequestOptions::JSON => $payload
                ]
            );

            $body = $response->getBody()->getContents();
            Log::debug
            (
                sprintf
                (
                    "MailApi::sendEmail template_identifier %s to_email %s body %s",
                    $template_identifier,
                    $to_email,
                    $body
                )
            );
            return json_decode($body, true);
        }
        catch (RequestException $ex) {
            Log::warning($ex);
            $this->cleanAccessToken();
            $response  = $ex->getResponse();
            $code = $response->getStatusCode();
            if($code == 403){
                // retry
                return $this->sendEmail($payload,  $template_identifier,  $to_email,  $subject, $cc_email, $bbc_email);
            }
            Log::error
            (
                sprintf
                (
                    "MailApi::sendEmail template_identifier %s to_email %s payload %s error %s",
                    $template_identifier,
                    $to_email,
                    json_encode($payload),
                    $ex->getMessage()
                )
            );
            throw $ex;
        }
        catch (\Exception $ex) {
            $this->cleanAccessToken();
            Log::error($ex);
            throw $ex;
        }
    }
}

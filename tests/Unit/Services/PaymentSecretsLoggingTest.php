<?php namespace Tests\Unit\Services;
/**
 * Copyright 2026 OpenStack Foundation
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

use App\Jobs\Payments\CreatePaymentProfileMQJob;
use App\Jobs\Payments\PaymentsMQJob;
use App\Jobs\Payments\UpdatePaymentProfileMQJob;
use App\Models\Foundation\Summit\Repositories\IPaymentGatewayProfileRepository;
use App\Services\Apis\AbstractOAuth2Api;
use App\Services\Apis\IPaymentsApi;
use App\Services\Apis\PaymentGateways\LawPayApi;
use App\Services\Apis\PaymentGateways\StripeApi;
use App\Services\Auth\OAuth2ClientFactory;
use App\Services\Model\Imp\PaymentGatewayProfileService;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use libs\utils\ICacheService;
use libs\utils\ITransactionService;
use Mockery;
use models\summit\IPaymentConstants;
use models\summit\ISummitRepository;
use models\summit\PaymentGatewayProfileFactory;
use models\summit\StripePaymentProfile;
use models\summit\Summit;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

/**
 * Class PaymentSecretsLoggingTest
 *
 * Production runs summit-api at DEBUG level, so whatever these classes log ends up in the log
 * store. Each test runs one code path that handles a payment gateway or OAuth2 secret, with
 * marked values, and asserts that none of the marked values appears in any log line (message
 * or context), while the identifiers needed to debug the call (event id, intent id, webhook id,
 * profile id) still do.
 *
 * No Laravel app is booted: a bare container provides the Log and Config facades, the logger
 * records every line, and the Stripe SDK is routed to an in-memory HTTP client.
 *
 * @package Tests\Unit\Services
 */
class PaymentSecretsLoggingTest extends TestCase
{
    private const StripeSecretKey = 'sk_live_leakcheck_api_key';
    private const StripeWebhookSecret = 'whsec_leakcheck_signing_secret';
    private const StripeCreatedWebhookSecret = 'whsec_leakcheck_created_endpoint';
    private const IntentClientSecret = 'pi_leakcheck_secret_client';
    private const LawPaySecretKey = 'lawpay_leakcheck_secret_key';
    private const OAuth2ClientSecret = 'oauth2_leakcheck_client_secret';
    private const OAuth2AccessToken = 'oauth2_leakcheck_access_token';
    private const ProfileLiveSecretKey = 'sk_live_leakcheck_profile_key';
    private const ProfileTestSecretKey = 'sk_test_leakcheck_profile_key';

    private Container $app;

    private object $logger;

    protected function setUp(): void
    {
        parent::setUp();
        Facade::clearResolvedInstances();
        $this->app = new Container();
        $this->logger = new class extends AbstractLogger {
            public array $lines = [];

            public function log($level, $message, array $context = []): void
            {
                $this->lines[] = $level . ' ' . (string)$message . ' ' . json_encode($context);
            }
        };
        $this->app->instance('log', $this->logger);
        $this->app->instance('config', new Repository([]));
        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        Facade::setFacadeApplication(null);
        Facade::clearResolvedInstances();
        Container::setInstance(null);
        Mockery::close();
        parent::tearDown();
    }

    private function assertLogsDoNotContain(string ...$secrets): void
    {
        $this->assertNotEmpty($this->logger->lines, 'the code path under test logged nothing');
        foreach ($this->logger->lines as $line) {
            foreach ($secrets as $secret) {
                $this->assertStringNotContainsString($secret, $line, 'a secret was written to the log');
            }
        }
    }

    private function assertLogsContain(string $needle): void
    {
        foreach ($this->logger->lines as $line) {
            if (str_contains($line, $needle)) {
                $this->addToAssertionCount(1);
                return;
            }
        }
        $this->fail(sprintf('no log line mentions %s', $needle));
    }

    /**
     * Routes the Stripe SDK to an in-memory client that answers every request with $body.
     */
    private function fakeStripe(array $body): void
    {
        ApiRequestor::setHttpClient(new class($body) implements ClientInterface {
            public function __construct(private array $body)
            {
            }

            public function request($method, $absUrl, $headers, $params, $hasFile)
            {
                return [json_encode($this->body), 200, []];
            }
        });
    }

    private function paymentIntent(): array
    {
        return [
            'id' => 'pi_leakcheck_intent',
            'object' => 'payment_intent',
            'status' => 'succeeded',
            'client_secret' => self::IntentClientSecret,
            'payment_method' => 'pm_leakcheck',
            'charges' => ['object' => 'list', 'data' => []],
        ];
    }

    private function stripeApi(): StripeApi
    {
        return new StripeApi([
            'secret_key' => self::StripeSecretKey,
            'webhook_secret_key' => self::StripeWebhookSecret,
        ]);
    }

    /**
     * Payment profile as returned by purchases-api, which the sync jobs and the service receive.
     */
    private function profilePayload(): array
    {
        return [
            'id' => 5,
            'provider' => IPaymentConstants::ProviderStripe,
            'application_type' => IPaymentConstants::ApplicationTypeRegistration,
            'is_active' => false,
            'test_mode_enabled' => true,
            'live_secret_key' => self::ProfileLiveSecretKey,
            'live_publishable_key' => 'pk_live_public',
            'test_secret_key' => self::ProfileTestSecretKey,
            'test_publishable_key' => 'pk_test_public',
            'live_webhook_secret' => self::StripeWebhookSecret,
            'test_webhook_secret' => self::StripeWebhookSecret,
        ];
    }

    public function testStripeApiConstructorDoesNotLogItsKeys(): void
    {
        $this->stripeApi();

        $this->assertLogsDoNotContain(self::StripeSecretKey, self::StripeWebhookSecret);
    }

    public function testStripeWebhookCallbackDoesNotLogTheSigningSecretNorTheRawEvent(): void
    {
        $payload = json_encode([
            'id' => 'evt_leakcheck_event',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => $this->paymentIntent()],
        ]);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, self::StripeWebhookSecret);
        $request = Request::create('/confirm', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => sprintf('t=%s,v1=%s', $timestamp, $signature),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $result = $this->stripeApi()->processCallback($request);

        $this->assertSame('pi_leakcheck_intent', $result['cart_id']);
        $this->assertLogsDoNotContain(self::StripeSecretKey, self::StripeWebhookSecret, self::IntentClientSecret, $signature);
        $this->assertLogsContain('evt_leakcheck_event');
        $this->assertLogsContain('pi_leakcheck_intent');
    }

    public function testStripeWebhookCreationDoesNotLogTheReturnedSigningSecret(): void
    {
        $this->fakeStripe([
            'id' => 'we_leakcheck_endpoint',
            'object' => 'webhook_endpoint',
            'secret' => self::StripeCreatedWebhookSecret,
            'livemode' => true,
            'url' => 'https://api.test/confirm',
        ]);

        $info = $this->stripeApi()->createWebHook('https://api.test/confirm');
        $this->stripeApi()->getWebHookById('we_leakcheck_endpoint');

        $this->assertSame(self::StripeCreatedWebhookSecret, $info['secret']);
        $this->assertLogsDoNotContain(self::StripeSecretKey, self::StripeWebhookSecret, self::StripeCreatedWebhookSecret);
        $this->assertLogsContain('we_leakcheck_endpoint');
    }

    public function testStripePaymentIntentLookupsDoNotLogTheClientSecret(): void
    {
        $this->fakeStripe($this->paymentIntent());

        $api = $this->stripeApi();
        $this->assertSame('succeeded', $api->getCartStatus('pi_leakcheck_intent'));
        $this->assertNotNull($api->getPaymentDetailsInfo('pi_leakcheck_intent'));

        $this->assertLogsDoNotContain(self::StripeSecretKey, self::IntentClientSecret);
        $this->assertLogsContain('pi_leakcheck_intent');
    }

    public function testStripeProfileDoesNotLogWebhookSigningSecrets(): void
    {
        $profile = new StripePaymentProfile();
        $profile->setLiveWebHookData(['id' => 'we_leakcheck_live', 'secret_key' => self::StripeWebhookSecret]);
        $profile->setTestWebHookData(['id' => 'we_leakcheck_test', 'secret_key' => self::StripeWebhookSecret]);

        $this->assertLogsDoNotContain(self::StripeWebhookSecret);
        $this->assertLogsContain('we_leakcheck_live');
    }

    public function testLawPayApiConstructorDoesNotLogItsSecretKey(): void
    {
        new LawPayApi([
            'secret_key' => self::LawPaySecretKey,
            'public_key' => 'lawpay_public',
            'account_id' => 'lawpay_account',
            'test_mode_enabled' => true,
        ]);

        $this->assertLogsDoNotContain(self::LawPaySecretKey);
    }

    public function testOAuth2ClientFactoryDoesNotLogTheClientSecret(): void
    {
        OAuth2ClientFactory::build(
            ['authorization_endpoint' => 'https://idp.test/authorize', 'token_endpoint' => 'https://idp.test/token'],
            ['client_id' => 'leakcheck_client', 'client_secret' => self::OAuth2ClientSecret, 'scopes' => 'payment-profile/read']
        );

        $this->assertLogsDoNotContain(self::OAuth2ClientSecret);
        $this->assertLogsContain('leakcheck_client');
    }

    public function testOAuth2ApiDoesNotLogTheAccessToken(): void
    {
        $cache = Mockery::mock(ICacheService::class);
        $cache->shouldReceive('getSingleValue')->andReturn(self::OAuth2AccessToken);
        $api = new class($cache) extends AbstractOAuth2Api {
            public function getAppName(): string
            {
                return 'LEAKCHECK';
            }

            public function getAppConfig(): array
            {
                return [];
            }

            public function token(): ?string
            {
                return $this->getAccessToken();
            }
        };

        $this->assertSame(self::OAuth2AccessToken, $api->token());
        $this->assertLogsDoNotContain(self::OAuth2AccessToken);
    }

    public function testProfileFactoryDoesNotLogTheProfileKeys(): void
    {
        PaymentGatewayProfileFactory::build(IPaymentConstants::ProviderStripe, $this->profilePayload() + ['set_webhooks' => true]);

        $this->assertLogsDoNotContain(self::ProfileLiveSecretKey, self::ProfileTestSecretKey, self::StripeWebhookSecret);
    }

    public function testProfileServiceDoesNotLogTheProfileKeys(): void
    {
        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')->andReturnUsing(fn (\Closure $callback) => $callback());
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(0);
        $summit->shouldReceive('addPaymentProfile');
        $summit->shouldReceive('getPaymentProfileById')->andReturn(new StripePaymentProfile());
        $summit->shouldReceive('getPaymentGateWayProfilePerApp')->andReturn(null);
        $service = new PaymentGatewayProfileService($tx_service);

        $service->addPaymentProfile($summit, $this->profilePayload());
        $service->updatePaymentProfile($summit, 1, $this->profilePayload());

        $this->assertLogsDoNotContain(self::ProfileLiveSecretKey, self::ProfileTestSecretKey, self::StripeWebhookSecret);
    }

    private function mqJob(): PaymentsMQJob
    {
        $job = Mockery::mock(PaymentsMQJob::class);
        $job->shouldReceive('payload')->andReturn([
            'data' => ['id' => 5, 'summit_id' => 70, 'application_type' => IPaymentConstants::ApplicationTypeRegistration],
        ]);
        $job->shouldReceive('delete')->once();
        return $job;
    }

    private function paymentsApi(): IPaymentsApi
    {
        $payments_api = Mockery::mock(IPaymentsApi::class);
        $payments_api->shouldReceive('getPaymentProfile')->with(70, 5)->andReturn($this->profilePayload());
        return $payments_api;
    }

    public function testProfileSyncJobsDoNotLogTheProfileKeys(): void
    {
        $summit_repository = Mockery::mock(ISummitRepository::class);
        $summit_repository->shouldReceive('getById')->with(70)->andReturn(null);
        $profile_repository = Mockery::mock(IPaymentGatewayProfileRepository::class);
        $profile_repository->shouldReceive('getByExternalId')->andReturn(null);
        $service = new PaymentGatewayProfileService(Mockery::mock(ITransactionService::class));

        (new CreatePaymentProfileMQJob($summit_repository, $service, $this->paymentsApi()))->handle($this->mqJob());
        (new UpdatePaymentProfileMQJob($summit_repository, $profile_repository, $service, $this->paymentsApi()))->handle($this->mqJob());

        $this->assertLogsDoNotContain(self::ProfileLiveSecretKey, self::ProfileTestSecretKey, self::StripeWebhookSecret);
    }

    public function testProfileSyncJobsDeleteTheJobWhenThePaymentProfileIsNotFound(): void
    {
        $payments_api = Mockery::mock(IPaymentsApi::class);
        $payments_api->shouldReceive('getPaymentProfile')->with(70, 5)->andReturn(null);
        $summit_repository = Mockery::mock(ISummitRepository::class);
        $summit_repository->shouldReceive('getById')->with(70)->andReturn(null);
        $profile_repository = Mockery::mock(IPaymentGatewayProfileRepository::class);
        $profile_repository->shouldReceive('getByExternalId')->andReturn(null);
        $service = new PaymentGatewayProfileService(Mockery::mock(ITransactionService::class));

        (new CreatePaymentProfileMQJob($summit_repository, $service, $payments_api))->handle($this->mqJob());
        (new UpdatePaymentProfileMQJob($summit_repository, $profile_repository, $service, $payments_api))->handle($this->mqJob());

        $this->assertTrue(true);
    }
}

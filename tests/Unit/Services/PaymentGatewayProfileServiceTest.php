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

use App\Services\Model\Imp\PaymentGatewayProfileService;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use libs\utils\ITransactionService;
use Mockery;
use models\exceptions\ValidationException;
use models\summit\IPaymentConstants;
use models\summit\PaymentGatewayProfile;
use models\summit\StripePaymentProfile;
use models\summit\Summit;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

/**
 * Class PaymentGatewayProfileServiceTest
 *
 * Covers {@see PaymentGatewayProfileService::addPaymentProfile} never leaving a webhook behind
 * at Stripe for a profile that does not end up stored.
 *
 * Building a Stripe profile with live keys and a summit creates its webhook at Stripe right
 * away (setLiveMode() / activate() call buildWebHook()), and a DB rollback cannot undo that
 * call. When the duplicate check ran after the build, a rejected profile left a live webhook
 * behind that nothing stored the signing secret for, and every delivery to it failed signature
 * verification. So:
 *  - a second active profile is rejected before anything reaches Stripe;
 *  - when the transaction fails after a webhook was created, that webhook is deleted again;
 *  - when the root transaction re-runs the closure, the webhook of the discarded attempt is
 *    deleted and the stored profile keeps its own.
 *
 * No Laravel app is booted: a bare container provides the Log facade, and its logger spy is
 * the assertion surface for "the gateway was never reached". In the duplicate test the 'url'
 * binding is deliberately absent, so reaching buildWebHook() would fail on the action() helper
 * before any network call. The compensation tests route the Stripe SDK to an in-memory HTTP
 * client and assert the exact requests it received.
 *
 * @package Tests\Unit\Services
 */
class PaymentGatewayProfileServiceTest extends TestCase
{
    private Container $app;

    /**
     * Spy bound as the Log facade root; see setUp().
     */
    private $log;

    /**
     * Number of times the mocked transaction closure actually executed.
     */
    private int $tx_invocations = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Facade::clearResolvedInstances();
        $this->app = new Container();
        $this->log = Mockery::spy(LoggerInterface::class);
        $this->app->instance('log', $this->log);
        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);
        $this->tx_invocations = 0;
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

    /**
     * Transaction service that really runs the closure, so the code under test executes.
     * $runner replaces the default "run it once and return its value", to simulate a commit
     * failure or a retried attempt; it receives a closure that runs the callback and counts it.
     */
    private function makeService(?\Closure $runner = null): PaymentGatewayProfileService
    {
        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (\Closure $callback) use ($runner) {
                $attempt = function () use ($callback) {
                    $this->tx_invocations++;
                    return $callback();
                };
                return is_null($runner) ? $attempt() : $runner($attempt);
            });

        return new PaymentGatewayProfileService($tx_service);
    }

    /**
     * Routes every Stripe SDK request to an in-memory fake, and binds the 'url' service that
     * buildWebHook() needs to render the confirm route. Returns the fake, whose $requests
     * lists each call as "METHOD /path".
     */
    private function fakeStripe(): object
    {
        $url = Mockery::mock(UrlGenerator::class);
        $url->shouldReceive('action')->andReturn('https://api.test/api/public/v1/summits/78/payments/Registration/confirm');
        $this->app->instance('url', $url);

        $stripe = new class implements ClientInterface {
            public array $requests = [];
            private int $next_id = 0;

            public function request($method, $absUrl, $headers, $params, $hasFile)
            {
                $path = parse_url($absUrl, PHP_URL_PATH);
                $this->requests[] = strtoupper($method) . ' ' . $path;
                if (strtolower($method) === 'post' && $path === '/v1/webhook_endpoints') {
                    $this->next_id++;
                    $body = [
                        'id' => 'we_test_' . $this->next_id,
                        'object' => 'webhook_endpoint',
                        'secret' => 'whsec_test_' . $this->next_id,
                        'livemode' => false,
                        'url' => $params['url'] ?? null,
                    ];
                } elseif (strtolower($method) === 'delete') {
                    $body = ['id' => basename($path), 'object' => 'webhook_endpoint', 'deleted' => true];
                } else {
                    $body = ['id' => basename($path), 'object' => 'webhook_endpoint'];
                }
                return [json_encode($body), 200, []];
            }
        };
        ApiRequestor::setHttpClient($stripe);
        return $stripe;
    }

    /**
     * A summit with no payment profile yet; it expects $added_times addPaymentProfile() calls.
     */
    private function summitWithoutProfile(int $added_times): Summit
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(78);
        $summit->shouldReceive('getPaymentGateWayProfilePerApp')->andReturn(null);
        $summit->shouldReceive('addPaymentProfile')->times($added_times);
        return $summit;
    }

    private function activeRegistrationProfile(): PaymentGatewayProfile
    {
        $profile = Mockery::mock(PaymentGatewayProfile::class);
        $profile->shouldReceive('isActive')->andReturn(true);
        $profile->shouldReceive('getApplicationType')->andReturn(IPaymentConstants::ApplicationTypeRegistration);
        return $profile;
    }

    /**
     * A summit that already has an active Registration profile.
     */
    private function summitWithActiveRegistrationProfile(): Summit
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(70);
        $summit->shouldReceive('getPaymentGateWayProfilePerApp')
            ->with(IPaymentConstants::ApplicationTypeRegistration)
            ->andReturn($this->activeRegistrationProfile());
        return $summit;
    }

    /**
     * Payload shape the purchases-api sync (CreatePaymentProfileMQJob) sends for an active,
     * live-mode Stripe profile.
     */
    private function activeLiveStripePayload(): array
    {
        return [
            'provider' => IPaymentConstants::ProviderStripe,
            'application_type' => IPaymentConstants::ApplicationTypeRegistration,
            'active' => true,
            'test_mode_enabled' => false,
            'live_secret_key' => 'sk_live_dummy',
            'live_publishable_key' => 'pk_live_dummy',
            'test_secret_key' => 'sk_test_dummy',
            'test_publishable_key' => 'pk_test_dummy',
            'external_id' => 5,
        ];
    }

    public function testRejectsSecondActiveProfileBeforeCreatingAnyWebhook(): void
    {
        $summit = $this->summitWithActiveRegistrationProfile();
        $summit->shouldNotReceive('addPaymentProfile');

        $error = null;
        try {
            $this->makeService()->addPaymentProfile($summit, $this->activeLiveStripePayload());
        } catch (ValidationException $ex) {
            $error = $ex;
        }

        $this->assertSame(1, $this->tx_invocations);
        $this->log->shouldNotHaveReceived('debug', function ($message) {
            return is_string($message) && str_contains($message, 'buildWebHook');
        });
        $this->assertNotNull($error, 'addPaymentProfile must reject a second active Registration profile.');
        $this->assertSame(
            'There is already an active Payment Profile for application type Registration,',
            $error->getMessage()
        );
    }

    public function testStillAddsAnInactiveProfileWhenAnActiveOneExists(): void
    {
        $summit = $this->summitWithActiveRegistrationProfile();
        $summit->shouldReceive('addPaymentProfile')->once();

        $profile = $this->makeService()->addPaymentProfile($summit, [
            'provider' => IPaymentConstants::ProviderStripe,
            'application_type' => IPaymentConstants::ApplicationTypeRegistration,
            'active' => false,
        ]);

        $this->assertSame(1, $this->tx_invocations);
        $this->assertInstanceOf(StripePaymentProfile::class, $profile);
        $this->assertFalse($profile->isActive());
    }

    public function testDeletesTheCreatedWebhookWhenTheTransactionFailsAfterCreatingIt(): void
    {
        $stripe = $this->fakeStripe();
        $commit_failure = new \RuntimeException('commit failed');

        $error = null;
        try {
            $this->makeService(function (\Closure $attempt) use ($commit_failure) {
                $attempt();
                throw $commit_failure;
            })->addPaymentProfile($this->summitWithoutProfile(1), $this->activeLiveStripePayload());
        } catch (\RuntimeException $ex) {
            $error = $ex;
        }

        $this->assertSame($commit_failure, $error, 'the original failure must reach the caller');
        $this->assertSame(1, $this->tx_invocations);
        $this->assertSame([
            'POST /v1/webhook_endpoints',
            'GET /v1/webhook_endpoints/we_test_1',
            'DELETE /v1/webhook_endpoints/we_test_1',
        ], $stripe->requests);
    }

    public function testDeletesOnlyTheWebhookOfAnAttemptThatWasRetried(): void
    {
        $stripe = $this->fakeStripe();

        // the first attempt is rolled back and re-run, as the root transaction does after a
        // transient DB error; only the second attempt's profile gets stored
        $profile = $this->makeService(function (\Closure $attempt) {
            $attempt();
            return $attempt();
        })->addPaymentProfile($this->summitWithoutProfile(2), $this->activeLiveStripePayload());

        $this->assertSame(2, $this->tx_invocations);
        $this->assertInstanceOf(StripePaymentProfile::class, $profile);
        $this->assertSame('we_test_2', $profile->getLiveWebhookId());
        $this->assertSame([
            'POST /v1/webhook_endpoints',
            'POST /v1/webhook_endpoints',
            'GET /v1/webhook_endpoints/we_test_1',
            'DELETE /v1/webhook_endpoints/we_test_1',
        ], $stripe->requests);
    }
}

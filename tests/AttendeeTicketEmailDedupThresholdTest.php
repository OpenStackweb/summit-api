<?php namespace Tests;
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

use App\Jobs\Emails\AbstractEmailJob;
use App\Jobs\Emails\IMailTemplatesConstants;
use App\Jobs\Emails\InviteAttendeeTicketEditionMail;
use App\Jobs\Emails\SummitAttendeeTicketEmail;
use App\Services\Apis\IMailApi;
use App\Services\Apis\IPasswordlessAPI;
use App\Services\Model\IMemberService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
use ReflectionObject;
use services\apis\IMarketingAPI;

/**
 * Covers the "already sent" de-duplication guard shared by the attendee ticket emails.
 *
 * The guard is driven by registration.attendee_invitation_email_threshold. A threshold of 0
 * means "no de-duplication window", but Cache::add() returns false for any TTL <= 0 without
 * touching the store, which used to make the guard suppress *every* send.
 *
 * Class AttendeeTicketEmailDedupThresholdTest
 */
final class AttendeeTicketEmailDedupThresholdTest extends TestCase
{
    const TO_EMAIL = 'test+dedup@test.com';

    const TICKET_ID = 91598;

    const TEMPLATE_IDENTIFIER = 'REGISTRATION_INVITE_ATTENDEE_TICKET_EDITION';

    protected function setUp(): void
    {
        parent::setUp();

        // keep the guard's cache off the shared redis store
        Config::set('cache.default', 'array');
        $this->app->forgetInstance('cache');
        $this->app->forgetInstance('cache.store');
        Facade::clearResolvedInstance('cache');
        Cache::flush();

        $this->app->instance(IMemberService::class, $this->buildMemberServiceMock());
        $this->app->instance(IPasswordlessAPI::class, $this->createMock(IPasswordlessAPI::class));
        $this->app->instance(IMarketingAPI::class, $this->buildMarketingApiMock());
    }

    public function testZeroThresholdStillSendsInviteAttendeeTicketEditionMail()
    {
        Config::set('registration.attendee_invitation_email_threshold', 0);

        $api = $this->buildMailApiSpy($calls);

        $this->buildJob(InviteAttendeeTicketEditionMail::class)->handle($api);

        $this->assertCount(
            1,
            $calls,
            'A threshold of 0 disables de-duplication, so the email must still be sent.'
        );
        $this->assertEquals(self::TO_EMAIL, $calls[0]);
    }

    public function testZeroThresholdStillSendsSummitAttendeeTicketEmail()
    {
        Config::set('registration.attendee_invitation_email_threshold', 0);

        $api = $this->buildMailApiSpy($calls);

        $this->buildJob(SummitAttendeeTicketEmail::class)->handle($api);

        $this->assertCount(
            1,
            $calls,
            'A threshold of 0 disables de-duplication, so the email must still be sent.'
        );
        $this->assertEquals(self::TO_EMAIL, $calls[0]);
    }

    public function testPositiveThresholdSuppressesTheSecondSendWithinTheWindow()
    {
        Config::set('registration.attendee_invitation_email_threshold', 5);

        $api = $this->buildMailApiSpy($calls);

        $this->buildJob(InviteAttendeeTicketEditionMail::class)->handle($api);
        $this->buildJob(InviteAttendeeTicketEditionMail::class)->handle($api);

        $this->assertCount(
            1,
            $calls,
            'Within a positive threshold window the same email must be sent only once.'
        );
    }

    /**
     * Builds the job past its entity-heavy constructor: the guard under test only reads
     * to_email, ticket_id and the configured threshold.
     *
     * @param string $class
     * @return AbstractEmailJob
     */
    private function buildJob(string $class): AbstractEmailJob
    {
        $job = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        $this->setProperty($job, 'to_email', self::TO_EMAIL);
        $this->setProperty($job, 'ticket_id', self::TICKET_ID);
        $this->setProperty($job, 'template_identifier', self::TEMPLATE_IDENTIFIER);
        $this->setProperty($job, 'payload', [
            IMailTemplatesConstants::summit_id => 1,
            IMailTemplatesConstants::owner_email => self::TO_EMAIL,
        ]);

        return $job;
    }

    private function setProperty(object $job, string $name, $value): void
    {
        $property = (new ReflectionObject($job))->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($job, $value);
    }

    /**
     * @param array|null $calls collects the to_email of every delivered email
     * @return IMailApi
     */
    private function buildMailApiSpy(?array &$calls): IMailApi
    {
        $calls = [];

        $api = $this->createMock(IMailApi::class);
        $api->method('sendEmail')->willReturnCallback(
            function (array $payload, string $template_identifier, string $to_email) use (&$calls): array {
                $calls[] = $to_email;
                return [];
            }
        );

        return $api;
    }

    private function buildMemberServiceMock(): IMemberService
    {
        $memberService = $this->createMock(IMemberService::class);
        // a known external user short-circuits the registration request branch
        $memberService->method('checkExternalUser')->willReturn(['id' => 1]);

        return $memberService;
    }

    private function buildMarketingApiMock(): IMarketingAPI
    {
        $marketingApi = $this->createMock(IMarketingAPI::class);
        $marketingApi->method('getConfigValues')->willReturn([]);

        return $marketingApi;
    }
}

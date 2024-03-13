<?php namespace Tests;
/**
 * Copyright 2024 OpenStack Foundation
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

use App\Jobs\Emails\Registration\PromoCodes\SponsorPromoCodeEmail;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\Summit;
use services\model\ISummitPromoCodeService;
use utils\FilterParser;

/**
 * Class PromoCodesServiceTest
 */
final class PromoCodesServiceTest extends TestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testSendAllAttendeeTicketsByAttendeeIds() {

        $summit_repository = EntityManager::getRepository(Summit::class);
        self::$summit = $summit_repository->find(3849);


        $service = App::make(ISummitPromoCodeService::class);

        $payload = [
            'email_flow_event'  => SponsorPromoCodeEmail::EVENT_SLUG,
            'promo_code_ids'    => [495],
            'excluded_promo_code_ids' => [],
            'test_email_recipient'    => 'test_recip@nomail.com',
            'outcome_email_recipient' => 'outcome_recip@nomail.com',
        ];

        $filterParam = [
            'id==495',
            'not_id==496||497',
            'sponsor==FNTECH',
            'tier==Default',
            'code==TEST_PPPC_711111256',
            'contact_email==test@nomail.com',
            'email_sent==0',
        ];

        $filter = FilterParser::parse($filterParam,
            [
                'id'      => ['=='],
                'not_id'  => ['=='],
                'sponsor' => ['=@', '@@', '=='],
                'tier'    => ['=@', '@@', '=='],
                'code'    => ['=@', '@@', '=='],
                'contact_email' => ['=@', '@@', '=='],
                'email_sent'    => ['=='],
            ]
        );

        $service->sendSponsorPromoCodes(self::$summit->getId(), $payload, $filter);
    }
}
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
use App\Models\Foundation\Main\IGroup;
use Illuminate\Support\Facades\App;
use services\model\ISummitPromoCodeService;
use utils\FilterParser;

/**
 * Class PromoCodesServiceTest
 */
final class PromoCodesServiceTest extends BrowserKitTestCase
{
    use InsertSummitTestData;
    use InsertMemberTestData;
    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testSendSponsorPromoCodesWithNotIn() {

        $service = App::make(ISummitPromoCodeService::class);

        $payload = [
            'email_flow_event'  => SponsorPromoCodeEmail::EVENT_SLUG,
            'test_email_recipient'    => 'test_recip@nomail.com',
        ];

        $filterParam = [
            'not_id=='.implode('||',[
                    self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-1]->getId(),
                    self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-3]->getId(),
                    self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-2]->getId()
                ]
            ),
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

        $this->assertFalse(self::$default_sponsors_promo_codes[0]->isEmailSent());

        $this->assertFalse(self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-1]->isEmailSent());
        $this->assertFalse(self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-2]->isEmailSent());
        $this->assertFalse(self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-3]->isEmailSent());

        $service->sendSponsorPromoCodes(self::$summit->getId(), $payload, $filter);

        $this->assertFalse(self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-1]->isEmailSent());
        $this->assertFalse(self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-2]->isEmailSent());
        $this->assertFalse(self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-3]->isEmailSent());
        $this->assertTrue(self::$default_sponsors_promo_codes[0]->isEmailSent());
    }

    public function testSendSponsorPromoCodesWithIn() {

        $service = App::make(ISummitPromoCodeService::class);

        $payload = [
            'email_flow_event'  => SponsorPromoCodeEmail::EVENT_SLUG,
            'test_email_recipient'    => 'test_recip@nomail.com',
        ];

        $filterParam = [
            'id=='.implode('||',[
                    self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-1]->getId(),
                    self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-3]->getId(),
                ]
            ),
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

        $this->assertFalse(self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-1]->isEmailSent());
        $this->assertFalse(self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-3]->isEmailSent());

        $service->sendSponsorPromoCodes(self::$summit->getId(), $payload, $filter);

        $this->assertTrue(self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-1]->isEmailSent());
        $this->assertTrue(self::$default_sponsors_promo_codes[count(self::$default_sponsors_promo_codes)-3]->isEmailSent());

    }
}
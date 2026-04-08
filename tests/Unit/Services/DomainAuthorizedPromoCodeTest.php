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

use models\exceptions\ValidationException;
use models\summit\DomainAuthorizedSummitRegistrationDiscountCode;
use models\summit\DomainAuthorizedSummitRegistrationPromoCode;
use models\summit\SummitTicketType;
use PHPUnit\Framework\TestCase;

/**
 * Class DomainAuthorizedPromoCodeTest
 * Unit tests for domain matching, audience filtering, and collision avoidance.
 * @package Tests\Unit\Services
 */
class DomainAuthorizedPromoCodeTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Domain matching — DomainAuthorizedPromoCodeTrait::matchesEmailDomain
    // -----------------------------------------------------------------------

    public function testExactDomainMatchSucceeds(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com']);
        $this->assertTrue($code->matchesEmailDomain('user@acme.com'));
    }

    public function testExactDomainMatchRejectsOtherDomain(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com']);
        $this->assertFalse($code->matchesEmailDomain('user@other.com'));
    }

    public function testTldSuffixMatchSucceeds(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['.edu']);
        $this->assertTrue($code->matchesEmailDomain('user@mit.edu'));
        $this->assertTrue($code->matchesEmailDomain('user@cs.stanford.edu'));
    }

    public function testTldSuffixMatchRejectsNonMatching(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['.edu']);
        $this->assertFalse($code->matchesEmailDomain('user@acme.com'));
    }

    public function testGovSuffixMatch(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['.gov']);
        $this->assertTrue($code->matchesEmailDomain('user@agency.gov'));
    }

    public function testExactEmailMatch(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['specific@email.com']);
        $this->assertTrue($code->matchesEmailDomain('specific@email.com'));
        $this->assertFalse($code->matchesEmailDomain('other@email.com'));
    }

    public function testCaseInsensitiveMatching(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@ACME.COM']);
        $this->assertTrue($code->matchesEmailDomain('user@acme.com'));
        $this->assertTrue($code->matchesEmailDomain('USER@ACME.COM'));
    }

    public function testEmptyDomainsPassesAll(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains([]);
        $this->assertTrue($code->matchesEmailDomain('anyone@anywhere.com'));
    }

    public function testMultiplePatternsMatchesAny(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com', '.edu', 'vip@special.org']);

        $this->assertTrue($code->matchesEmailDomain('user@acme.com'));
        $this->assertTrue($code->matchesEmailDomain('student@mit.edu'));
        $this->assertTrue($code->matchesEmailDomain('vip@special.org'));
        $this->assertFalse($code->matchesEmailDomain('nobody@random.net'));
    }

    public function testEmptyEmailReturnsFalse(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com']);
        $this->assertFalse($code->matchesEmailDomain(''));
    }

    // -----------------------------------------------------------------------
    // checkSubject — throws ValidationException on failure
    // -----------------------------------------------------------------------

    public function testCheckSubjectThrowsForNonMatchingEmail(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com']);

        $this->expectException(ValidationException::class);
        $code->checkSubject('user@other.com', null);
    }

    public function testCheckSubjectSucceedsForMatchingEmail(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains(['@acme.com']);

        $result = $code->checkSubject('user@acme.com', null);
        $this->assertTrue($result);
    }

    // -----------------------------------------------------------------------
    // AutoApplyPromoCodeTrait
    // -----------------------------------------------------------------------

    public function testAutoApplyDefaultsFalse(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $this->assertFalse($code->getAutoApply());
    }

    public function testAutoApplyCanBeSet(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAutoApply(true);
        $this->assertTrue($code->getAutoApply());
    }

    // -----------------------------------------------------------------------
    // QuantityPerAccount
    // -----------------------------------------------------------------------

    public function testQuantityPerAccountDefaultsToZero(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $this->assertEquals(0, $code->getQuantityPerAccount());
    }

    public function testQuantityPerAccountCanBeSet(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setQuantityPerAccount(5);
        $this->assertEquals(5, $code->getQuantityPerAccount());
    }

    // -----------------------------------------------------------------------
    // ClassName constants
    // -----------------------------------------------------------------------

    public function testDiscountCodeClassName(): void
    {
        $code = new DomainAuthorizedSummitRegistrationDiscountCode();
        $this->assertEquals('DOMAIN_AUTHORIZED_DISCOUNT_CODE', $code->getClassName());
    }

    public function testPromoCodeClassName(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $this->assertEquals('DOMAIN_AUTHORIZED_PROMO_CODE', $code->getClassName());
    }

    // -----------------------------------------------------------------------
    // IDomainAuthorizedPromoCode interface
    // -----------------------------------------------------------------------

    public function testImplementsInterface(): void
    {
        $discountCode = new DomainAuthorizedSummitRegistrationDiscountCode();
        $promoCode = new DomainAuthorizedSummitRegistrationPromoCode();

        $this->assertInstanceOf(\models\summit\IDomainAuthorizedPromoCode::class, $discountCode);
        $this->assertInstanceOf(\models\summit\IDomainAuthorizedPromoCode::class, $promoCode);
    }

    // -----------------------------------------------------------------------
    // SummitTicketType — WithPromoCode audience
    // -----------------------------------------------------------------------

    public function testWithPromoCodeAudienceConstant(): void
    {
        $this->assertEquals('WithPromoCode', SummitTicketType::Audience_With_Promo_Code);
        $this->assertContains('WithPromoCode', SummitTicketType::AllowedAudience);
    }

    // -----------------------------------------------------------------------
    // RemainingQuantityPerAccount transient property
    // -----------------------------------------------------------------------

    public function testRemainingQuantityPerAccountTransient(): void
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $this->assertNull($code->getRemainingQuantityPerAccount());

        $code->setRemainingQuantityPerAccount(3);
        $this->assertEquals(3, $code->getRemainingQuantityPerAccount());
    }

    // -----------------------------------------------------------------------
    // Domain matching on discount code variant
    // -----------------------------------------------------------------------

    public function testDiscountCodeDomainMatching(): void
    {
        $code = new DomainAuthorizedSummitRegistrationDiscountCode();
        $code->setAllowedEmailDomains(['@partner.com', '.edu']);

        $this->assertTrue($code->matchesEmailDomain('user@partner.com'));
        $this->assertTrue($code->matchesEmailDomain('student@university.edu'));
        $this->assertFalse($code->matchesEmailDomain('user@random.org'));
    }
}

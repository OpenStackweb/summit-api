<?php namespace Tests\Unit\Rules;
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

use App\Rules\AllowedEmailDomainsArray;
use Tests\TestCase;

final class AllowedEmailDomainsArrayTest extends TestCase
{
    private function rule(): AllowedEmailDomainsArray
    {
        return new AllowedEmailDomainsArray();
    }

    /**
     * @dataProvider validValuesProvider
     */
    public function testValidValuesPass($value): void
    {
        $this->assertTrue($this->rule()->passes('allowed_email_domains', $value));
    }

    /**
     * @dataProvider invalidValuesProvider
     */
    public function testInvalidValuesFail($value): void
    {
        $this->assertFalse($this->rule()->passes('allowed_email_domains', $value));
    }

    public static function validValuesProvider(): array
    {
        return [
            'empty array (no restriction)'        => [[]],
            'single-label TLD'                    => [['.edu']],
            'single-label .gov'                   => [['.gov']],
            'single-label .co'                    => [['.co']],
            'multi-label .co.uk'                  => [['.co.uk']],
            'multi-label .com.au'                 => [['.com.au']],
            'multi-label .ac.uk'                  => [['.ac.uk']],
            '@domain'                             => [['@acme.com']],
            '@domain with multi-level TLD'        => [['@uni.ac.uk']],
            'exact email'                         => [['user@example.com']],
            'mixed valid patterns'                => [['@acme.com', '.edu', 'alice@bob.org']],
            'entry padded with whitespace'        => [['  .edu  ']],
            'uppercase single-label TLD'          => [['.EDU']],
            'mixed-case multi-label TLD'          => [['.Co.Uk']],
        ];
    }

    public static function invalidValuesProvider(): array
    {
        return [
            'non-array string'                    => ['string'],
            'non-array null'                      => [null],
            'non-array int'                       => [42],
            'empty string entry'                  => [['']],
            'whitespace-only entry'               => [['   ']],
            'non-string numeric entry'            => [[123]],
            'nested array entry'                  => [[['nested']]],
            'bare dot'                            => [['.']],
            'leading double dot'                  => [['..com']],
            'trailing dot'                        => [['.com.']],
            'consecutive dots mid-pattern'        => [['.co..uk']],
            'leading hyphen in label'             => [['.-edu']],
            'bare @'                              => [['@']],
            '@ followed by dot'                   => [['@.com']],
            'bare token (no @ or leading .)'     => [['acme.com']],
            'one bad apple in otherwise valid'    => [['@acme.com', '.co.uk', 'bogus']],
        ];
    }

    public function testMessageReturnsNonEmptyString(): void
    {
        $message = $this->rule()->message();
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
    }
}

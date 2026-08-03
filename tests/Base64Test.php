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

use App\Utils\Base64;
use PHPUnit\Framework\TestCase;

/**
 * Class Base64Test
 * Badge/ticket QR artifacts travel base64-encoded as a URL path segment; the standard
 * alphabet's "/" can never survive path routing (Laravel rawurldecodes the path before
 * matching), so the helper must also accept the URL-safe alphabet (base64url, RFC 4648 §5:
 * "-" for "+", "_" for "/"). Standard base64 must keep decoding byte-identical.
 */
final class Base64Test extends TestCase
{
    public function testStandardAlphabetStillDecodes()
    {
        // "+/+/" = indices 62,63,62,63 = bytes FB FF BF (hand-derived from the RFC 4648 table)
        $this->assertTrue(Base64::looksLikeBase64("+/+/"));
        $this->assertSame("\xfb\xff\xbf", Base64::tryBase64Decode("+/+/"));
    }

    public function testUrlSafeAlphabetIsAccepted()
    {
        // same payload as "+/+/", url-safe spelling
        $this->assertTrue(Base64::looksLikeBase64("-_-_"));
        $this->assertSame("\xfb\xff\xbf", Base64::tryBase64Decode("-_-_"));
    }

    public function testUrlSafeAndStandardSpellingsDecodeToTheSameBytes()
    {
        $standard = Base64::tryBase64Decode("QUFB/QkJC+Q0PT0=");
        $urlSafe  = Base64::tryBase64Decode("QUFB_QkJC-Q0PT0=");
        $this->assertNotNull($standard);
        $this->assertSame($standard, $urlSafe);
    }

    public function testUrlSafeWithoutPaddingIsPadded()
    {
        // "-_" = indices 62,63 = 11111011 = byte FB after padding to "-_=="
        $this->assertTrue(Base64::looksLikeBase64("-_"));
        $this->assertSame("\xfb", Base64::tryBase64Decode("-_"));
    }

    public function testNonBase64InputIsRejected()
    {
        $this->assertFalse(Base64::looksLikeBase64("BADGE_X|123|a@b.com|Ada Lovelace"));
        $this->assertFalse(Base64::looksLikeBase64(""));
        $this->assertNull(Base64::tryBase64Decode("!!!"));
    }
}

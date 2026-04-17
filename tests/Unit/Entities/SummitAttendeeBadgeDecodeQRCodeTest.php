<?php

namespace Tests\Unit\Entities;

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

use models\summit\Summit;
use models\summit\SummitAttendeeBadge;
use Tests\TestCase;

class SummitAttendeeBadgeDecodeQRCodeTest extends TestCase
{
    private function createMockSummit(): Summit
    {
        $summit = $this->createMock(Summit::class);
        $summit->method('getId')->willReturn(69);
        $summit->method('getTicketQRPrefix')->willReturn('TICKET_2025OCPGLO');
        $summit->method('getBadgeQRPrefix')->willReturn('BADGE_2025OCPGLO');
        $summit->method('hasQRCodesEncKey')->willReturn(false);
        return $summit;
    }

    /**
     * Test that plaintext QR codes with spaces preserve the spaces.
     * This is the regression test for the bug where spaces were converted to +.
     */
    public function test_decodeQRCodeFor_plaintext_with_space_preserves_space(): void
    {
        $summit = $this->createMockSummit();

        // Plaintext QR code with space in the name field (last field)
        $qr_code = 'BADGE_2025OCPGLO|TICKET_2025OCPGLO_6849117831062192101035|yada-tak@spp.co.jp|Takeshi YADA';

        $decoded = SummitAttendeeBadge::decodeQRCodeFor($summit, $qr_code);

        // The space in "Takeshi YADA" should be preserved, not converted to +
        $this->assertStringContainsString('Takeshi YADA', $decoded);
        $this->assertStringNotContainsString('Takeshi+YADA', $decoded);
    }

    /**
     * Test that plaintext QR codes with multiple spaces preserve all spaces.
     */
    public function test_decodeQRCodeFor_plaintext_with_multiple_spaces(): void
    {
        $summit = $this->createMockSummit();

        // QR code with multiple spaces in name
        $qr_code = 'BADGE_2025OCPGLO|TICKET_123|test@example.com|John Patrick Smith';

        $decoded = SummitAttendeeBadge::decodeQRCodeFor($summit, $qr_code);

        $this->assertStringContainsString('John Patrick Smith', $decoded);
        $this->assertStringNotContainsString('John+Patrick+Smith', $decoded);
    }

    /**
     * Test that base64-encoded QR codes with spaces (from form encoding) are decoded correctly.
     * When base64 is transmitted via HTTP form encoding, + may become space.
     * The str_replace should restore + before base64 decoding.
     */
    public function test_decodeQRCodeFor_base64_with_spaces_decodes_correctly(): void
    {
        $summit = $this->createMockSummit();

        // Create a base64-encoded QR code where + has been replaced with space
        // Original: "BADGE_2025OCPGLO|TICKET_123|test@example.com|John Doe"
        $original = 'BADGE_2025OCPGLO|TICKET_123|test@example.com|John Doe';
        $base64 = base64_encode($original);

        // Simulate form encoding where + becomes space
        $base64_with_spaces = str_replace('+', ' ', $base64);

        $decoded = SummitAttendeeBadge::decodeQRCodeFor($summit, $base64_with_spaces);

        // Should decode back to the original string
        $this->assertEquals($original, $decoded);
    }

    /**
     * Test that normal base64-encoded QR codes (without space replacement) work.
     */
    public function test_decodeQRCodeFor_base64_normal(): void
    {
        $summit = $this->createMockSummit();

        $original = 'BADGE_2025OCPGLO|TICKET_456|user@test.com|Alice';
        $base64 = base64_encode($original);

        $decoded = SummitAttendeeBadge::decodeQRCodeFor($summit, $base64);

        $this->assertEquals($original, $decoded);
    }

    /**
     * Test that URL-encoded plaintext QR codes are decoded correctly.
     */
    public function test_decodeQRCodeFor_url_encoded_plaintext(): void
    {
        $summit = $this->createMockSummit();

        // QR code with URL-encoded space (%20)
        $qr_code = 'BADGE_2025OCPGLO|TICKET_789|test@example.com|Bob%20Jones';

        $decoded = SummitAttendeeBadge::decodeQRCodeFor($summit, $qr_code);

        // rawurldecode should convert %20 to space
        $this->assertStringContainsString('Bob Jones', $decoded);
    }
}

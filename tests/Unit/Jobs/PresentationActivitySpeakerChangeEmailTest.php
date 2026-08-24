<?php namespace Tests\Unit\Jobs;
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

use App\Jobs\Emails\Schedule\PresentationActivitySpeakerChangeEmail;
use Illuminate\Support\Facades\Config;
use models\exceptions\ValidationException;
use models\summit\Presentation;
use models\summit\PresentationSpeaker;
use Tests\TestCase;

/**
 * Class PresentationActivitySpeakerChangeEmailTest
 * @package Tests\Unit\Jobs
 */
final class PresentationActivitySpeakerChangeEmailTest extends TestCase
{
    public function testConstructorRejectsInvalidRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PresentationActivitySpeakerChangeEmail(
            new Presentation(),
            new PresentationSpeaker(),
            'NotARole',
            'Added'
        );
    }

    public function testConstructorRejectsInvalidAction(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PresentationActivitySpeakerChangeEmail(
            new Presentation(),
            new PresentationSpeaker(),
            'Speaker',
            'NotAnAction'
        );
    }

    public function testConstructorThrowsWhenRecipientNotConfigured(): void
    {
        Config::set('cfp.speaker_change_notification_email', null);

        $this->expectException(ValidationException::class);

        $presentation = new Presentation();
        $presentation->setTitle('Test Presentation');

        new PresentationActivitySpeakerChangeEmail(
            $presentation,
            new PresentationSpeaker(),
            'Speaker',
            'Added'
        );
    }
}

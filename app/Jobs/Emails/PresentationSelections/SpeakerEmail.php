<?php namespace App\Jobs\Emails\PresentationSelections;

/**
 * Copyright 2021 OpenStack Foundation
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

/**
 * Class SpeakerEmail
 * @package App\Jobs\Emails\PresentationSelections
 */
class SpeakerEmail extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SELECTIONS_SPEAKER_EMAIL';
    const EVENT_NAME = 'SUMMIT_SELECTIONS_SPEAKER_EMAIL';
    const DEFAULT_TEMPLATE = 'SUMMIT_SELECTIONS_SPEAKER_EMAIL';
}
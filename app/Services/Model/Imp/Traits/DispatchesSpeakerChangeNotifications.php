<?php namespace App\Services\Model\Imp\Traits;
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

/**
 * Trait DispatchesSpeakerChangeNotifications
 * @package App\Services\Model\Imp\Traits
 */
trait DispatchesSpeakerChangeNotifications
{
    /**
     * Dispatches queued speaker/moderator change notifications only after the caller's
     * enclosing transaction has committed, so a queued notification never outlives a
     * save that ends up rolling back.
     * @param array $pending_notifications
     * @return void
     */
    private function dispatchSpeakerChangeNotifications(array $pending_notifications): void
    {
        foreach ($pending_notifications as $notification) {
            PresentationActivitySpeakerChangeEmail::dispatch(...$notification);
        }
    }
}

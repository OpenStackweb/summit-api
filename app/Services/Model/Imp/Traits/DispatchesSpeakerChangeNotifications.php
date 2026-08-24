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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

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
     *
     * Because the caller's write is already durable by the time we get here, a notification
     * failure must never propagate: it would surface as an HTTP error on a request that
     * actually succeeded. The recipient is optional platform config, so an unconfigured
     * deployment simply logs and sends nothing, and a single failing notification never
     * cancels the rest of the batch.
     *
     * @param array $pending_notifications
     * @return void
     */
    private function dispatchSpeakerChangeNotifications(array $pending_notifications): void
    {
        if (count($pending_notifications) === 0) return;

        if (empty(Config::get(PresentationActivitySpeakerChangeEmail::RecipientConfigKey))) {
            Log::warning
            (
                sprintf
                (
                    "DispatchesSpeakerChangeNotifications::dispatchSpeakerChangeNotifications %s is not configured, skipping %s speaker change notification(s).",
                    PresentationActivitySpeakerChangeEmail::RecipientConfigKey,
                    count($pending_notifications)
                )
            );
            return;
        }

        foreach ($pending_notifications as $notification) {
            try {
                PresentationActivitySpeakerChangeEmail::dispatch(...$notification);
            } catch (\Exception $ex) {
                Log::warning("DispatchesSpeakerChangeNotifications::dispatchSpeakerChangeNotifications failed to dispatch speaker change notification.");
                Log::warning($ex);
            }
        }
    }
}

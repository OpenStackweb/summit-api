<?php namespace App\Services\Model\Imp\Notifications;
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
use models\summit\Presentation;
use models\summit\PresentationSpeaker;

/**
 * Collects speaker/moderator changes made to published presentations while a transaction is
 * still open, and turns them into queued notifications once that transaction has committed.
 *
 * The contract is deliberately structural: whoever CONSTRUCTS a collector is the one that
 * dispatches it, right after its own tx_service->transaction() returns. Methods that merely
 * take a collector as a parameter only ever add to it, never dispatch it. That keeps a
 * notification from outliving a save that ends up rolling back, without any caller having to
 * describe its own transaction nesting.
 *
 * Class SpeakerChangeNotifications
 * @package App\Services\Model\Imp\Notifications
 */
final class SpeakerChangeNotifications
{
    /**
     * @var array
     */
    private $pending = [];

    /**
     * @param Presentation $presentation
     * @param PresentationSpeaker $speaker
     * @param string $role
     * @param string $action
     * @return void
     */
    public function add(Presentation $presentation, PresentationSpeaker $speaker, string $role, string $action): void
    {
        $this->pending[] = [$presentation, $speaker, $role, $action];
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->pending) === 0;
    }

    /**
     * Queues everything collected so far and empties the collector.
     *
     * By the time this runs the caller's write is already durable, so a notification failure
     * must never propagate: it would surface as an HTTP error on a request that actually
     * succeeded. The recipient is optional platform config, so an unconfigured deployment
     * simply logs and sends nothing, and a single failing notification never cancels the rest.
     *
     * @return void
     */
    public function dispatch(): void
    {
        $pending = $this->pending;
        $this->pending = [];

        if (count($pending) === 0) return;

        if (empty(Config::get(PresentationActivitySpeakerChangeEmail::RecipientConfigKey))) {
            Log::warning
            (
                sprintf
                (
                    "SpeakerChangeNotifications::dispatch %s is not configured, skipping %s speaker change notification(s).",
                    PresentationActivitySpeakerChangeEmail::RecipientConfigKey,
                    count($pending)
                )
            );
            return;
        }

        foreach ($pending as $notification) {
            try {
                PresentationActivitySpeakerChangeEmail::dispatch(...$notification);
            } catch (\Exception $ex) {
                Log::warning("SpeakerChangeNotifications::dispatch failed to dispatch speaker change notification.");
                Log::warning($ex);
            }
        }
    }
}

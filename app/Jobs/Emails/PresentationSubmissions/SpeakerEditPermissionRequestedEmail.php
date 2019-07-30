<?php namespace App\Jobs\Emails\PresentationSubmissions;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Models\Foundation\Summit\Speakers\SpeakerEditPermissionRequest;
use Illuminate\Support\Facades\Config;

/**
 * Class SpeakerEditPermissionRequestedEmail
 * @package App\Jobs\Emails\PresentationSubmissions
 */
class SpeakerEditPermissionRequestedEmail extends AbstractEmailJob
{
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_SPEAKER_EDIT_PERMISSION_REQUEST';

    // metadata
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_SPEAKER_EDIT_PERMISSION_REQUEST';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSIONS_SPEAKER_EDIT_PERMISSION_REQUEST';

    /**
     * SpeakerEditPermissionRequestedEmail constructor.
     * @param SpeakerEditPermissionRequest $request
     * @param string $token
     */
    public function __construct(SpeakerEditPermissionRequest $request, string $token)
    {
        $payload = [];
        $payload['requested_by_full_name'] = $request->getRequestedBy()->getFullName();
        $payload['speaker_full_name'] = $request->getSpeaker()->getFullName();
        $payload['token'] = $token;
        $payload['link'] = $request->getConfirmationLink($request->getSpeaker()->getId(), $token);
        $payload['tenant_name'] = Config::get("app.tenant_name");
        $payload['requested_by_email'] = $request->getRequestedBy()->getEmail();
        $payload['speaker_email'] = $request->getSpeaker()->getEmail();
        parent::__construct($payload, self::DEFAULT_TEMPLATE, $payload['speaker_email']);
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

}
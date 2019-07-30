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
 * Class SpeakerEditPermissionApprovedEmail
 * @package App\Jobs\Emails\PresentationSubmissions
 */
class SpeakerEditPermissionApprovedEmail extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSIONS_SPEAKER_EDIT_PERMISSION_APPROVED';
    const EVENT_NAME = 'SUMMIT_SUBMISSIONS_SPEAKER_EDIT_PERMISSION_APPROVED';
    const DEFAULT_TEMPLATE ='SUMMIT_SUBMISSIONS_SPEAKER_EDIT_PERMISSION_APPROVED';

    /**
     * SpeakerEditPermissionRequested constructor.
     * @param SpeakerEditPermissionRequest $request
     */
    public function __construct(SpeakerEditPermissionRequest $request)
    {
        $payload = [];
        $payload['requested_by_full_name'] = $request->getRequestedBy()->getFullName();
        $payload['speaker_full_name'] = $request->getSpeaker()->getFullName();
        $payload['speaker_management_link'] =
        $payload['tenant_name'] = Config::get("app.tenant_name");
        $payload['requested_by_email'] = $request->getRequestedBy()->getEmail();
        parent::__construct($payload, self::DEFAULT_TEMPLATE, $payload['requested_by_email']);
    }

}
<?php namespace App\Http\Controllers\Utils;
/**
 * Copyright 2025 OpenStack Foundation
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

use App\Http\Controllers\SummitFinderStrategyFactory;
use App\Http\Exceptions\HTTP403ForbiddenException;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\RSVP;
use models\summit\Summit;
use models\summit\SummitEvent;

trait Assertions
{
    // helper methods
    private function getSummitOr404(int $summit_id): Summit
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit))
            throw new EntityNotFoundException("Summit not found.");
        return $summit;
    }

    private function getScheduleEventOr404(Summit $summit, int $event_id): SummitEvent
    {
        $summit_event = $summit->getScheduleEvent(intval($event_id));
        if (is_null($summit_event))
            throw new EntityNotFoundException("Summit event not found or not published.");
        return $summit_event;
    }

    private function getEventOr404(Summit $summit, int $event_id): SummitEvent
    {
        $summit_event = $summit->getEvent(intval($event_id));
        if (is_null($summit_event))
            throw new EntityNotFoundException("Summit event not found.");
        return $summit_event;
    }

    private function getCurrentMemberOr403(): Member
    {
        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member))
            throw new HTTP403ForbiddenException("Current member is not Present");
        return $current_member;
    }

    /**
     * @param SummitEvent $summit_event
     * @param int $rsvp_id
     * @return RSVP
     * @throws HTTP403ForbiddenException
     */
    private function getRSVPOr404(SummitEvent $summit_event, int $rsvp_id): RSVP
    {
        $rsvp = $summit_event->getRSVPById($rsvp_id);
        if (is_null($rsvp))
            throw new HTTP403ForbiddenException("RSVP not found.");
        return $rsvp;
    }

    /**
     * @param LaravelRequest $request
     * @return array|UploadedFile|UploadedFile[]|null
     * @throws ValidationException
     */
    private function getFile(LaravelRequest $request): ?UploadedFile
    {
        $file = $request->file('file');

        // Extra diagnostics: surface the underlying PHP upload error if invalid
        if (!$file || !$file->isValid()) {
            Log::debug("Assertions::getFile file is not valid");
            $errorCode = $file?->getError();
            $errorMsg = match ($errorCode) {
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in the form',
                UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder (upload_tmp_dir)',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk (permissions)',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
                default => 'Unknown upload error',
            };
            throw new ValidationException("Upload error ({$errorCode}): {$errorMsg}");
        }
        return $file;
    }
}
<?php namespace App\Jobs\Emails\PresentationSubmissions\SelectionProcess;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Services\Utils\Facades\EmailExcerpt;
use App\Services\utils\IEmailExcerptService;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\SubmitterAnnouncementSummitEmail;
use models\summit\Summit;
use utils\Filter;

/**
 * Class PresentationSubmitterSelectionProcessEmailFactory
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
final class PresentationSubmitterSelectionProcessEmailFactory
{
    /**
     * @var array
     */
    public static $valid_types = [
        SubmitterAnnouncementSummitEmail::TypeAccepted,
        SubmitterAnnouncementSummitEmail::TypeAlternate,
        SubmitterAnnouncementSummitEmail::TypeAlternate,
        SubmitterAnnouncementSummitEmail::TypeAcceptedAlternate,
        SubmitterAnnouncementSummitEmail::TypeAcceptedRejected,
        SubmitterAnnouncementSummitEmail::TypeAlternateRejected,
    ];

    /**
     * @param string $type
     * @return bool
     */
    public static function isValidType($type){
        return in_array($type, self::$valid_types);
    }

    /**
     * @param Summit $summit
     * @param Member $submitter
     * @param string $type
     * @param string|null $test_email_recipient
     * @param Filter|null $filter
     * @param callable|null $onSuccess
     */
    public static function send
    (
        Summit $summit,
        Member $submitter,
        string $type,
        ?string $test_email_recipient,
        ?Filter $filter = null,
        callable $onSuccess = null
    ){

        Log::debug(sprintf("PresentationSubmitterSelectionProcessEmailFactory::send speaker %s type %s", $submitter->getEmail(), $type));

        switch ($type){
            case SubmitterAnnouncementSummitEmail::TypeAccepted:
                PresentationSubmitterSelectionProcessAcceptedOnlyEmail::dispatch
                (
                    $summit,
                    $submitter,
                    $test_email_recipient,
                    $filter
                );
            break;
            case SubmitterAnnouncementSummitEmail::TypeAlternate:
                PresentationSubmitterSelectionProcessAlternateOnlyEmail::dispatch
                (
                    $summit,
                    $submitter,
                    $test_email_recipient,
                    $filter
                );
                break;
            case SubmitterAnnouncementSummitEmail::TypeRejected:
                PresentationSubmitterSelectionProcessRejectedOnlyEmail::dispatch
                (
                    $summit,
                    $submitter,
                    $test_email_recipient,
                    $filter
                );
            break;
            case SubmitterAnnouncementSummitEmail::TypeAcceptedAlternate:
                PresentationSubmitterSelectionProcessAcceptedAlternateEmail::dispatch
                (
                    $summit,
                    $submitter,
                    $test_email_recipient,
                    $filter
                );
                break;
            case SubmitterAnnouncementSummitEmail::TypeAcceptedRejected:
                PresentationSubmitterSelectionProcessAcceptedRejectedEmail::dispatch
                (
                    $summit,
                    $submitter,
                    $test_email_recipient,
                    $filter
                );
                break;
            case SubmitterAnnouncementSummitEmail::TypeAlternateRejected:
                PresentationSubmitterSelectionProcessAlternateRejectedEmail::dispatch
                (
                    $summit,
                    $submitter,
                    $test_email_recipient,
                    $filter
                );
                break;
        }

        if (!is_null($onSuccess)) {
            $onSuccess($submitter->getEmail(), IEmailExcerptService::EmailLineType, $type);
        }
    }
}
<?php namespace App\Jobs\Emails\PresentationSubmissions\Invitations;
/*
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

use App\Jobs\Emails\AbstractEmailJob;
use http\Exception\InvalidArgumentException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\summit\SummitSubmissionInvitation;

/**
 * Class InviteSubmissionEmail
 * @package App\Jobs\Emails\PresentationSubmissions\Invitations
 */
class InviteSubmissionEmail
    extends AbstractEmailJob
{

    /**
     * @param SummitSubmissionInvitation $invitation
     * @param array $extra_data
     */
    public function __construct(SummitSubmissionInvitation $invitation, array $extra_data)
    {
        Log::debug
        (
            sprintf
            (
                "InviteSubmissionEmail::____construct invitation %s email %s extra_data %s",
                $invitation->getId(),
                $invitation->getEmail(),
                json_encode($extra_data)
            )
        );

        $summit = $invitation->getSummit();
        $owner_email = $invitation->getEmail();

        $payload = [];
        $payload['owner_email'] = $owner_email;
        $payload['first_name'] = $invitation->getFirstName();
        $payload['last_name'] = $invitation->getLastName();
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();

        $base_url = Config::get('cfp.base_url', null);

        if (empty($base_url))
            throw new \InvalidArgumentException("missing cfp.base_url value");

        $back_url = sprintf("%s/app/%s/all-plans", $base_url, $summit->getRawSlug());
        $payload['selection_plan_name'] = '';
        $payload['selection_plan_id'] = 0;
        $payload['selection_plan_submission_start_date'] = '';
        $payload['selection_plan_submission_end_date'] = '';

        if (isset($extra_data['selection_plan_id'])) {
            $selection_plan_id = intval($extra_data['selection_plan_id']);
            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            Log::debug(sprintf("InviteSubmissionEmail::____construct selection plan %s was provided", $selection_plan_id));

            if(is_null($selection_plan)){
                throw new InvalidArgumentException
                (
                    sprintf
                    (
                        "Selection Plan %s is not a valid one for Summit %s.",
                        $selection_plan_id,
                        $summit->getId()
                    )
                );
            }
            $payload['selection_plan_name'] = $selection_plan->getName();
            $payload['selection_plan_id'] = $selection_plan->getId();
            $submissionBeingDateLocal = $selection_plan->getSubmissionBeginDateLocal();
            $payload['selection_plan_submission_start_date'] = !is_null($submissionBeingDateLocal) ? $submissionBeingDateLocal->format('F d, Y') : "";
            $submissionEndDateLocal = $selection_plan->getSubmissionEndDateLocal();
            $payload['selection_plan_submission_end_date'] = !is_null($submissionEndDateLocal) ? $submissionEndDateLocal->format('F d, Y') : "";

            $back_url = $back_url . '/' . $selection_plan_id;
        }

        $payload['magic_link'] = sprintf
        (
            "%s/auth/login?login_hint=%s&otp_login_hint=%s&BackUrl=%s"
            , $base_url
            , urlencode($invitation->getEmail())
            , urlencode($invitation->getOtp())
            , urlencode($back_url)
        );

        $payload['support_email'] = Config::get("cfp.support_email", null);

        if (empty($payload['support_email']))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $owner_email);
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_SUBMISSION_INVITE_REGISTRATION';
    const EVENT_NAME = 'SUMMIT_SUBMISSION_INVITE_REGISTRATION';
    const DEFAULT_TEMPLATE = 'SUMMIT_SUBMISSION_INVITE_REGISTRATION';
}
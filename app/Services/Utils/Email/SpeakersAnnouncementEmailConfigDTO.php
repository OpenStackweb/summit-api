<?php namespace App\Services\Utils\Email;
/*
 * Copyright 2022 OpenStack Foundation
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

use Illuminate\Support\Facades\Log;


/**
 * Class SpeakersAnnouncementEmailConfigDTO
 * @package App\Services\Utils\Email
 */
final class SpeakersAnnouncementEmailConfigDTO
{
    /**
     * @var bool
     */
    private $should_resend;

    /**
     * @var bool
     */
    private $should_send_copy_2_submitter;

    public function __construct()
    {
        Log::debug("SpeakersAnnouncementEmailConfigDTO::__construct");
        $this->should_resend = true;
        $this->should_send_copy_2_submitter = false;
    }

    /**
     * @return bool
     */
    public function shouldResend(): bool
    {
        return $this->should_resend;
    }

    /**
     * @param bool $should_resend
     */
    public function setShouldResend(bool $should_resend): void
    {
        Log::debug(sprintf( "SpeakersAnnouncementEmailConfigDTO::setShouldResend should_resend %b", $should_resend));
        $this->should_resend = $should_resend;
    }

    /**
     * @return bool
     */
    public function shouldSendCopy2Submitter(): bool
    {
        return $this->should_send_copy_2_submitter;
    }

    /**
     * @param bool $should_send_copy_2_submitter
     */
    public function setShouldSendCopy2Submitter(bool $should_send_copy_2_submitter): void
    {
        Log::debug(sprintf("SpeakersAnnouncementEmailConfigDTO::setShouldResend should_send_copy_2_submitter %b", $should_send_copy_2_submitter));
        $this->should_send_copy_2_submitter = $should_send_copy_2_submitter;
    }

}
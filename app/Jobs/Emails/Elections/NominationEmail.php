<?php namespace App\Jobs\Emails\Elections;
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
use App\Models\Foundation\Elections\Election;
use models\main\Member;
use Illuminate\Support\Facades\Log;
/**
 * Class NominationEmail
 * @package App\Jobs\Emails\Elections
 */
class NominationEmail extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'ELECTIONS_NOMINATION_NOTICE';
    const EVENT_NAME = 'ELECTIONS_NOMINATION_NOTICE';
    const DEFAULT_TEMPLATE ='ELECTIONS_NOMINATION_NOTICE';

    /**
     * NominationEmail constructor.
     * @param Election $election
     * @param Member $candidate
     */
    public function __construct(Election  $election, Member $candidate)
    {
        Log::debug(sprintf("NominationEmail::__construct election %s candidate %s", $election->getId(), $candidate->getId()));
        $payload = [];
        $payload['election_title'] = $election->getName();
        $payload['election_app_deadline'] = '';
        $nominationDeadline = $election->getNominationDeadline();
        $payload['member_id'] = $candidate->getId();
        if(!is_null($nominationDeadline))
            $payload['election_app_deadline'] = $election->getNominationDeadline()->format("l j F Y h:i A T");
        $payload['candidate_full_name'] = $candidate->getFullName();
        $payload['candidate_email'] = $candidate->getEmail();

        if(empty($payload['candidate_full_name'])){
            $payload['candidate_full_name'] = $payload['candidate_email'];
        }
        $payload['candidate_has_accepted_nomination'] = $candidate->getLatestCandidateProfile()->isHasAcceptedNomination();
        $payload['candidate_nominations_count'] = $candidate->getElectionApplicationsCountFor($election);
        parent::__construct($payload, self::DEFAULT_TEMPLATE, $candidate->getEmail());
    }

}
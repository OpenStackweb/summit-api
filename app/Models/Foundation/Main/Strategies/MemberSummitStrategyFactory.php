<?php namespace App\Models\Foundation\Main\Strategies;

/**
 * Copyright 2024 OpenStack Foundation
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

use models\main\Member;

class MemberSummitStrategyFactory
{
    /**
     * @param Member $member
     * @return IMemberSummitStrategy
     */
    public static function getMemberSummitStrategy(Member $member): IMemberSummitStrategy
    {
        if ($member->isSponsorUser() || $member->isExternalSponsorUser()) {
            return new SponsorMemberSummitStrategy($member->getId());
        }
        return new MemberSummitStrategy($member->getId());
    }
}
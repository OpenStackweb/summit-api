<?php namespace App\Events\Registration;
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
use Illuminate\Queue\SerializesModels;
/**
 * Class UpdateAttendeesData
 * @package App\Events\Registration
 */
class MemberDataUpdatedExternally
{

    use SerializesModels;

    /**
     * @var int
     */
    private $member_id;

    /**
     * NewMember constructor.
     * @param int $member_id
     */
    public function __construct(int $member_id)
    {
        $this->member_id = $member_id;
    }

    /**
     * @return int
     */
    public function getMemberId(): int
    {
        return $this->member_id;
    }
}
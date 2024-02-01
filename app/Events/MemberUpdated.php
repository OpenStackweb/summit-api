<?php namespace App\Events;
/*
 * Copyright 2021 OpenStack Foundation
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
use Illuminate\Support\Facades\Log;

/**
 * Class MemberUpdated
 * @package App\Events
 */
final class MemberUpdated
{
    use SerializesModels;

    /**
     * @var int
     */
    private $member_id;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $first_name;

    /**
     * @var string
     */
    private $last_name;

    /**
     * @var string
     */
    private $company;

    /**
     * @param int $member_id
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param string $company
     */
    public function __construct
    (
        int $member_id,
        string $email,
        ?string $first_name,
        ?string $last_name,
        ?string $company
    )
    {
        $this->member_id = $member_id;
        $this->email = $email;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->company = $company;
        Log::debug
        (
            sprintf
            (
                "MemberUpdated::construct member_id %s email %s first_name %s last_name %s company %s",
                $member_id,
                $email,
                $first_name,
                $last_name,
                $company
            )
        );
    }


    /**
     * @return int
     */
    public function getMemberId(): int
    {
        return $this->member_id;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    /**
     * @return string
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

}
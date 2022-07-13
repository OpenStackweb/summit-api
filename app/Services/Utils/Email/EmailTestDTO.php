<?php namespace App\Services\utils;
/**
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
 * Class EmailTestDTO
 * @package App\Services\utils
 */
final class EmailTestDTO
{
    /**
     * @var string
     */
    private $email_address;

    /**
     * @return string
     */
    public function getEmailAddress(): string {
        Log::debug(sprintf("EmailTestDTO::setEmailAddress getEmailAddress %s", $this->email_address));
        return $this->email_address;
    }

    /**
     * @param string $email_address
     * @return void
     */
    public function setEmailAddress(string $email_address) : void {
        Log::debug(sprintf("EmailTestDTO::setEmailAddress email_address %s", $email_address));
        $this->email_address = $email_address;
    }
}
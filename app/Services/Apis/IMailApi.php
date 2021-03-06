<?php namespace App\Services\Apis;
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

/**
 * Interface IMailApi
 * @package App\Services\Apis
 */
interface IMailApi
{
    /**
     * @param array $payload
     * @param string $template_identifier
     * @param string $to_email
     * @param string|null $subject
     * @param string|null $cc_email
     * @param string|null $bbc_email
     * @return array
     */
    public function sendEmail(array $payload, string $template_identifier, string $to_email, string $subject = null, string $cc_email = null , string $bbc_email = null):array ;
}
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

/**
 * Interface EmailExcerptService
 * @package App\Services\utils
 */
interface IEmailExcerptService
{
    const InfoType = 'INFO';
    const ErrorType = 'ERROR';
    const SpeakerEmailType = 'SPEAKER';
    /**
     * @param array $value
     * @return void
     */
    public function add(array $value) : void;

    /**
     * @return void
     */
    public function clearReport() : void;

    /**
     * @return array
     */
    public function getReport(): array;

    public function addEmailSent():void;

    public function generateEmailCountLine():void;

    public function addInfoMessage(string $message):void;

    public function addErrorMessage(string $message):void;
}
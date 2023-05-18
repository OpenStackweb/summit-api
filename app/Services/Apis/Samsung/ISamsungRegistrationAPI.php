<?php namespace App\Services\Apis\Samsung;
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

use models\summit\Summit;

/**
 * Interface ISamsungRegistrationAPI
 * @package App\Services\Apis\Samsung
 */
interface ISamsungRegistrationAPI
{
    /**
     * @param Summit $summit
     * @param string $userId
     * @param string $region
     * @return mixed
     */
    public function checkUser(Summit $summit, string $userId, string $region = Regions::US);

    /**
     * @param Summit $summit
     * @param string $email
     * @param string $region
     * @return mixed
     */
    public function checkEmail(Summit $summit, string $email, string $region = Regions::US);

    /**
     * @param Summit $summit
     * @param string $region
     * @return mixed
     */
    public function userList(Summit $summit, string $region = Regions::US);
}
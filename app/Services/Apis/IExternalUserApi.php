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
use Exception;
/**
 * Interface IExternalUserApi
 * @package App\Services\Apis
 */
interface IExternalUserApi {
  /**
   * @param string $email
   * @return null|mixed
   * @throws Exception
   */
  public function getUserByEmail(string $email);

  /**
   * @param int $id
   * @return null|mixed
   * @throws Exception
   */
  public function getUserById(int $id);

  /**
   * @param string $email
   * @param string|null $first_name
   * @param string|null $last_name
   * @param string|null $company
   * @return mixed
   * @throws Exception
   */
  public function registerUser(
    string $email,
    ?string $first_name,
    ?string $last_name,
    ?string $company = "",
  );

  /**
   * @param int $id
   * @param string|null $first_name
   * @param string|null $last_name
   * @return mixed
   * @throws Exception
   */
  public function updateUser(
    int $id,
    ?string $first_name,
    ?string $last_name,
    ?string $company_name,
  );

  /**
   * @param string $email
   * @return mixed
   * @throws Exception
   */
  public function getUserRegistrationRequest(string $email);

  /**
   * @param int $id
   * @param string|null $first_name
   * @param string|null $last_name
   * @param string|null $company_name
   * @param string|null $country
   * @return mixed
   * @throws Exception
   */
  public function updateUserRegistrationRequest(
    int $id,
    ?string $first_name,
    ?string $last_name,
    ?string $company_name,
    ?string $country,
  );
}

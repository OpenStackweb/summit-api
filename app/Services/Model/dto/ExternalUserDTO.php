<?php namespace App\Services\Model\dto;
/**
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

/**
 * Class ExternalUserDTO
 * @package App\Services\Model\dto
 */
class ExternalUserDTO {
  private $id;

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
   * @var bool
   */
  private $active;

  /**
   * @var bool
   */
  private $email_verified;

  /**
   * ExternalUserDTO constructor.
   * @param $id
   * @param string $email
   * @param string $first_name
   * @param string $last_name
   * @param bool $active
   * @param bool $email_verified
   */
  public function __construct(
    $id,
    string $email,
    ?string $first_name,
    ?string $last_name,
    bool $active = false,
    bool $email_verified = false,
  ) {
    $this->id = $id;
    $this->email = $email;
    $this->first_name = $first_name;
    $this->last_name = $last_name;
    $this->active = $active;
    $this->email_verified = $email_verified;
  }

  /**
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getEmail(): ?string {
    return $this->email;
  }

  /**
   * @return string|null
   */
  public function getFirstName(): ?string {
    return $this->first_name;
  }

  /**
   * @return string|null
   */
  public function getLastName(): ?string {
    return $this->last_name;
  }

  /**
   * @return bool
   */
  public function isActive(): bool {
    return $this->active;
  }

  /**
   * @return bool
   */
  public function isEmailVerified(): bool {
    return $this->email_verified;
  }
}

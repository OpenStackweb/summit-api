<?php namespace App\Jobs;
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
use App\Services\Model\IMemberService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\SummitAttendee;
/**
 * Class UpdateIDPMemberInfo
 * @package App\Jobs
 */
class UpdateIDPMemberInfo implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * @var string
   */
  public $user_email;
  /**
   * @var string
   */
  public $user_first_name;
  /**
   * @var string
   */
  public $user_last_name;
  /**
   * @var string
   */
  public $user_company_name;

  /**
   * @param string $user_email
   * @param string|null $user_first_name
   * @param string|null $user_last_name
   * @param string|null $user_company_name
   */
  public function __construct(
    string $user_email,
    ?string $user_first_name,
    ?string $user_last_name,
    ?string $user_company_name,
  ) {
    $this->user_email = $user_email;
    $this->user_first_name = $user_first_name;
    $this->user_last_name = $user_last_name;
    $this->user_company_name = $user_company_name;
  }
  /**
   * @param IMemberService $service
   */
  public function handle(IMemberService $service) {
    Log::debug(sprintf("UpdateIDPMemberInfo::handle user updated %s", $this->user_email));

    try {
      //Check if user exists
      $user = $service->checkExternalUser($this->user_email);

      //If user exists => update it
      if (!is_null($user)) {
        $service->updateExternalUser(
          $user["id"],
          $this->user_first_name,
          $this->user_last_name,
          $this->user_company_name,
        );
        return;
      }

      //If user doesn't exist => check if there is a pending registration request, if so => update it
      $service->updatePendingRegistrationRequest(
        $this->user_email,
        true,
        $this->user_first_name,
        $this->user_last_name,
        $this->user_company_name,
        null,
      );
    } catch (\Exception $ex) {
      Log::error($ex);
    }
  }
}

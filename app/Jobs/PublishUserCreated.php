<?php namespace App\Jobs;
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
use App\Services\Model\IMemberService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;

/**
 * Class PublishUserCreated
 * RabbitMQ job
 * @package App\Jobs
 */
class PublishUserCreated implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * @var int
   */
  public $user_id;
  /**
   * @var string
   */
  public $user_email;

  /**
   * @param IMemberService $service
   */
  public function handle(IMemberService $service) {
    Log::debug(
      sprintf("PublishUserCreated::handle user created %s %s", $this->user_id, $this->user_email),
    );

    try {
      $service->registerExternalUserById($this->user_id);
    } catch (EntityNotFoundException $ex) {
      Log::warning($ex);
    } catch (\Exception $ex) {
      Log::error($ex);
    }
  }
}

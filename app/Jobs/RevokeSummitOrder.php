<?php namespace App\Jobs;
/*
 * Copyright 2024 OpenStack Foundation
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

use App\Services\Model\ISummitOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class RevokeSummitOrder
 * @package App\Jobs
 */
final class RevokeSummitOrder implements ShouldQueue {
  public $tries = 1;

  public $timeout = 0;

  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * @var int
   */
  private $order_id;

  /**
   * @param int $order_id
   */
  public function __construct(int $order_id) {
    $this->order_id = $order_id;
  }

  /**
   * @param ISummitOrderService $service
   * @throws \models\exceptions\EntityNotFoundException
   */
  public function handle(ISummitOrderService $service) {
    Log::debug(sprintf("RevokeSummitOrder::handle order id %s", $this->order_id));
    $service->processOrder2Revoke($this->order_id);
  }

  public function failed(\Throwable $exception) {
    Log::error($exception);
  }
}

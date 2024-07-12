<?php namespace App\Console\Commands;
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
use Illuminate\Console\Command;
use services\model\ISummitService;
use Exception;
use Illuminate\Support\Facades\Log;
/**
 * Class SummitEventSetAvgRateProcessor
 * @package App\Console\Commands
 */
class SummitEventSetAvgRateProcessor extends Command {
  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = "summit:feedback-avg-rate-processor";

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "summit:feedback-avg-rate-processor";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Calculate all AVG feedback rate for all schedule for all ongoing summits";

  /**
   * @var ISummitService
   */
  private $summit_service;

  /**
   * SummitEventSetAvgRateProcessor constructor.
   * @param ISummitService $summit_service
   */
  public function __construct(ISummitService $summit_service) {
    parent::__construct();
    $this->summit_service = $summit_service;
  }

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle() {
    try {
      $this->info("processing SummitEventSetAvgRateProcessor");
      $start = time();
      $this->summit_service->calculateFeedbackAverageForOngoingSummits();
      $end = time();
      $delta = $end - $start;
      $this->info(sprintf("execution call %s seconds", $delta));
    } catch (Exception $ex) {
      Log::error($ex);
    }
  }
}

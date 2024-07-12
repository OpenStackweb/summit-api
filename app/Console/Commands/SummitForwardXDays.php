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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use services\model\ISummitService;
use Exception;
/**
 * Class SummitForwardXDays
 * @package App\Console\Commands
 */
class SummitForwardXDays extends Command {
  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = "summit:forward-x-days";

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "summit:forward-x-days {tenant} {summit_id} {days} {--negative} {--check-ended}";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Advance a summit forward by x days";

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
      $tenant = $this->argument("tenant");
      if (empty($tenant)) {
        throw new \InvalidArgumentException("tenant is required");
      }

      $current_tenant = Config::get("app.tenant_name");

      Log::debug(
        sprintf("SummitForwardXDays::handle tenant %s current_tenant %s", $tenant, $current_tenant),
      );

      if (strtoupper($tenant) != strtoupper($current_tenant)) {
        Log::warning(sprintf("SummitForwardXDays::handle exiting bc tenants are not the same"));
        return;
      }

      $summit_id = $this->argument("summit_id");
      if (empty($summit_id)) {
        throw new \InvalidArgumentException("summit_id is required");
      }

      $days = $this->argument("days");
      if (empty($days)) {
        throw new \InvalidArgumentException("days is required");
      }

      $negative = $this->option("negative");
      $check_summit_ends = $this->option("check-ended");
      $this->info("processing SummitForwardXDays");
      $start = time();

      $days = intval($days);

      $this->summit_service->advanceSummit(
        intval($summit_id),
        $days,
        $negative,
        $check_summit_ends,
      );
      $end = time();
      $delta = $end - $start;
      $this->info(sprintf("execution call %s seconds", $delta));
    } catch (Exception $ex) {
      Log::warning($ex);
    }
  }
}

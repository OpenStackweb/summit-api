<?php namespace App\Console\Commands;
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

use App\Models\Foundation\Main\Repositories\IAuditLogRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class PurgeAuditLogCommand extends Command {
  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = "audit:purge-log";

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "audit:purge-log {summit_id} {date_backward_from}";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Audit Log Purge Command";

  /**
   * @var IAuditLogRepository
   */
  private $repository;

  public function __construct(IAuditLogRepository $repository) {
    parent::__construct();
    $this->repository = $repository;
  }

  /**
   * Execute the console command.
   *
   * @return void
   */
  public function handle() {
    try {
      $summit_id = $this->argument("summit_id");
      if (empty($summit_id)) {
        throw new \InvalidArgumentException("summit_id is required");
      }

      $date_backward_from = $this->argument("date_backward_from");
      if (empty($date_backward_from)) {
        throw new \InvalidArgumentException("date_backward_from is required");
      }

      $this->info(
        sprintf("PurgeAuditLogCommand::handle purge audit log for summit %s", $summit_id),
      );
      $start = time();

      $this->repository->deleteOldLogEntries(
        intval($summit_id),
        Carbon::parse($date_backward_from)->toDate(),
      );

      $end = time();
      $delta = $end - $start;
      $this->info(
        sprintf("PurgeAuditLogCommand::handle execution call processed in %s seconds", $delta),
      );
    } catch (Exception $ex) {
      Log::error($ex);
    }
  }
}

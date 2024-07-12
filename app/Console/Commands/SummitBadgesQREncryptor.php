<?php namespace App\Console\Commands;
/**
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

use Database\Seeders\SummitEmailFlowTypeSeeder;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use libs\utils\ICacheService;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitService;
use Illuminate\Support\Facades\Config;

/**
 * Class SummitBadgesQREncryptor
 * @package App\Console\Commands
 */
final class SummitBadgesQREncryptor extends Command {
  /**
   * @var ISummitService
   */
  private $service;

  /**
   * SummitBadgesQREncryptor constructor.
   * @param ISummitService $service
   */
  public function __construct(ISummitService $service) {
    parent::__construct();
    $this->service = $service;
  }

  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = "summit:badges-qr-encryptor";

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "summit:badges-qr-encryptor {summit_id}";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Encrypt All Summit badge QR codes";

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle() {
    $summit_id = $this->argument("summit_id");

    if (empty($summit_id)) {
      throw new \InvalidArgumentException("summit_id is required");
    }

    $this->info("starting to regenerate badge QR codes for summit id {$summit_id}");
    $start = time();
    $this->service->regenerateBadgeQRCodes(intval($summit_id));
    $end = time();
    $delta = $end - $start;
    $this->info(sprintf("execution call %s seconds", $delta));
  }
}

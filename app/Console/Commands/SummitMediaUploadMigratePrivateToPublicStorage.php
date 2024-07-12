<?php namespace App\Console\Commands;
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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;
use services\model\ISummitService;

/**
 *
 */
class SummitMediaUploadMigratePrivateToPublicStorage extends Command {
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
   * The console command name.
   *
   * @var string
   */
  protected $name = "summit:migrate-private-2-public-storage";

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "summit:migrate-private-2-public-storage {summit_id} {media_upload_type_id}";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Migrate media upload type from private storage to public storage for a particular summit";

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle() {
    try {
      $summit_id = $this->argument("summit_id");
      if (empty($summit_id)) {
        throw new \InvalidArgumentException("summit_id is required");
      }

      $media_upload_type_id = $this->argument("media_upload_type_id");
      if (empty($media_upload_type_id)) {
        throw new \InvalidArgumentException("media_upload_type_id is required");
      }

      $this->info(
        sprintf(
          "SummitMediaUploadMigratePrivateToPublicStorage::handle processing summit %s media upload type %s",
          $summit_id,
          $media_upload_type_id,
        ),
      );
      $start = time();

      $res = $this->summit_service->migratePrivateStorage2PublicStorage(
        intval($summit_id),
        intval($media_upload_type_id),
      );

      $end = time();
      $delta = $end - $start;
      $this->info(
        sprintf(
          "SummitMediaUploadMigratePrivateToPublicStorage::handle execution call processed %s in %s seconds",
          $res,
          $delta,
        ),
      );
    } catch (Exception $ex) {
      Log::warning($ex);
    }
  }
}

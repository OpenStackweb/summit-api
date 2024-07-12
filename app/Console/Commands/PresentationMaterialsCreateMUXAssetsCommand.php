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
use App\Services\Model\IPresentationVideoMediaUploadProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
/**
 * Class PresentationMaterialsCreateMUXAssetsCommand
 * @package App\Console\Commands
 */
final class PresentationMaterialsCreateMUXAssetsCommand extends Command {
  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = "summit:presentation-materials-mux-assets";

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = "summit:presentation-materials-mux-assets {summit_id} {mounting_folder?} {event_id?}";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = "Process Presentation Videos and ingest on MUX";

  /**
   * @param IPresentationVideoMediaUploadProcessor $service
   */
  public function handle(IPresentationVideoMediaUploadProcessor $service) {
    $summit_id = $this->argument("summit_id");

    $event_id = $this->argument("event_id");

    if (empty($summit_id)) {
      throw new \InvalidArgumentException("summit_id is required");
    }

    $mountingFolder = $this->argument("mounting_folder");
    if (empty($mountingFolder)) {
      $mountingFolder = Config::get("mediaupload.mounting_folder");
    }

    Log::debug(
      sprintf(
        "starting to process published presentations for summit id %s mountingFolder %s event id %s",
        $summit_id,
        $mountingFolder,
        $event_id,
      ),
    );
    $this->info(
      sprintf(
        "starting to process published presentations for summit id %s mountingFolder %s event id %s",
        $summit_id,
        $mountingFolder,
        $event_id,
      ),
    );

    if (empty($event_id)) {
      $service->processPublishedPresentationFor(intval($summit_id), $mountingFolder);
      return;
    }

    $service->processEvent(intval($event_id), $mountingFolder);
  }
}

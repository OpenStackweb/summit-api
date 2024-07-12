<?php namespace App\Jobs\Emails\Registration\PromoCodes;
/**
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
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
use services\model\ISummitPromoCodeService;
use utils\FilterParser;
/**
 * Class ProcessSponsorPromoCodesJob
 * @package App\Jobs\Emails\Registration\Invitations
 */
class ProcessSponsorPromoCodesJob implements ShouldQueue {
  public $tries = 1;

  // no timeout
  public $timeout = 0;

  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  private $summit_id;

  private $payload;

  private $filter;

  /**
   * ProcessSponsorPromoCodesJob constructor.
   * @param Summit $summit
   * @param array $payload
   * @param $filter
   */
  public function __construct(Summit $summit, array $payload, $filter) {
    $this->summit_id = $summit->getId();
    $this->payload = $payload;
    $this->filter = $filter;
  }

  /**
   * @param ISummitPromoCodeService $service
   * @throws \utils\FilterParserException
   */
  public function handle(ISummitPromoCodeService $service) {
    Log::debug(sprintf("ProcessSponsorPromoCodesJob::handle summit id %s", $this->summit_id));

    try {
      $filter = !is_null($this->filter)
        ? FilterParser::parse($this->filter, [
          "id" => ["=="],
          "not_id" => ["=="],
          "code" => ["@@", "=@", "=="],
          "notes" => ["@@", "=@", "=="],
          "description" => ["@@", "=@"],
          "tag" => ["@@", "=@", "=="],
          "tag_id" => ["=="],
          "sponsor_company_name" => ["@@", "=@", "=="],
          "sponsor_id" => ["=="],
          "contact_email" => ["@@", "=@", "=="],
          "tier_name" => ["@@", "=@", "=="],
          "email_sent" => ["=="],
        ])
        : null;

      $service->sendSponsorPromoCodes($this->summit_id, $this->payload, $filter);

      Log::debug(
        sprintf("ProcessSponsorPromoCodesJob::handle summit id %s has finished", $this->summit_id),
      );
    } catch (\Exception $ex) {
      Log::error($ex);
    }
  }

  public function failed(\Throwable $exception) {
    Log::error(sprintf("ProcessSponsorPromoCodesJob::failed %s", $exception->getMessage()));
  }
}

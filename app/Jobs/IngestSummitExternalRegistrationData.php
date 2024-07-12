<?php namespace App\Jobs;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Jobs\Emails\Registration\ExternalIngestion\SuccessfulIIngestionEmail;
use App\Jobs\Emails\Registration\ExternalIngestion\UnsuccessfulIIngestionEmail;
use App\Models\Foundation\Summit\Registration\ISummitExternalRegistrationFeedType;
use App\Services\Model\IRegistrationIngestionService;
use App\Services\Model\ISummitOrderExtraQuestionTypeService;
use App\Services\Model\ISummitTicketTypeService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\ValidationException;
use models\summit\ISummitRepository;
use models\summit\Summit;
/**
 * Class IngestSummitExternalRegistrationData
 * @package App\Jobs
 */
class IngestSummitExternalRegistrationData implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $tries = 1;

  public $timeout = 0;
  /**
   * @var int
   */
  private $summit_id;

  /**
   * @var string
   */
  private $email_to;

  /**
   * IngestSummitExternalRegistrationData constructor.
   * @param int $summit_id
   * @param null|string $email_to
   */
  public function __construct(int $summit_id, ?string $email_to = null) {
    $this->summit_id = $summit_id;
    $this->email_to = $email_to;
  }

  /**
   * @param ISummitRepository $summit_repository
   * @param ISummitTicketTypeService $ticketTypeService
   * @param ISummitOrderExtraQuestionTypeService $extraQuestionTypeService
   * @param IRegistrationIngestionService $service
   * @param ITransactionService $tx_service
   */
  public function handle(
    ISummitRepository $summit_repository,
    ISummitTicketTypeService $ticketTypeService,
    ISummitOrderExtraQuestionTypeService $extraQuestionTypeService,
    IRegistrationIngestionService $service,
    ITransactionService $tx_service,
  ) {
    try {
      Log::debug("IngestSummitExternalRegistrationData::handle");

      $summit = $tx_service->transaction(function () use ($summit_repository, $service) {
        $summit = $summit_repository->getById($this->summit_id);
        if (is_null($summit) || !$summit instanceof Summit) {
          return null;
        }
        return $summit;
      });

      if (is_null($summit)) {
        Log::debug("IngestSummitExternalRegistrationData::handle summit is null");
        return;
      }

      if (
        $summit->getExternalRegistrationFeedType() ==
        ISummitExternalRegistrationFeedType::Eventbrite
      ) {
        // first re seed ticket types
        $ticketTypeService->seedSummitTicketTypesFromEventBrite($summit);
        // then re seed extra questions
        $extraQuestionTypeService->seedSummitOrderExtraQuestionTypesFromEventBrite($summit);
      }

      // and finally ingest all data
      $service->ingestSummit($summit);

      if (!empty($this->email_to)) {
        Log::debug(
          sprintf(
            "IngestSummitExternalRegistrationData::handle - sending result email to %s",
            $this->email_to,
          ),
        );
        SuccessfulIIngestionEmail::dispatch($this->email_to, $summit);
      }
    } catch (ValidationException $ex) {
      Log::warning($ex);
      if (!empty($this->email_to)) {
        $summit = $summit_repository->getById($this->summit_id);
        if (is_null($summit) || !$summit instanceof Summit) {
          return;
        }
        UnsuccessfulIIngestionEmail::dispatch($ex->getMessage(), $this->email_to, $summit);
      }
    } catch (\Exception $ex) {
      Log::error($ex);
      if (!empty($this->email_to)) {
        $summit = $summit_repository->getById($this->summit_id);
        if (is_null($summit) || !$summit instanceof Summit) {
          return;
        }
        UnsuccessfulIIngestionEmail::dispatch($ex->getMessage(), $this->email_to, $summit);
      }
    }
  }

  public function failed(\Throwable $exception) {
    Log::error(
      sprintf("IngestSummitExternalRegistrationData::failed %s", $exception->getMessage()),
    );
  }
}

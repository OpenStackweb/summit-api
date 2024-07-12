<?php namespace App\Services\Model\Strategies\TicketFinder;
/*
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

use App\Models\Foundation\Summit\Registration\ISummitExternalRegistrationFeedType;
use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeedFactory;
use App\Services\Model\IRegistrationIngestionService;
use App\Services\Model\Strategies\TicketFinder\Strategies\TicketFinderByExternalFeedStrategy;
use App\Services\Model\Strategies\TicketFinder\Strategies\TicketFinderByIdStrategy;
use App\Services\Model\Strategies\TicketFinder\Strategies\TicketFinderByNumberStrategy;
use App\Utils\AES;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\Summit;
use models\summit\SummitAttendeeBadge;
use Illuminate\Support\Facades\App;
use models\summit\SummitAttendeeTicket;

/**
 * Class TicketFinderStrategyFactory
 * @package App\Services\Model\Strategies\TicketFinder
 */
final class TicketFinderStrategyFactory implements ITicketFinderStrategyFactory {
  private $ticket_repository;

  /**
   * @var IExternalRegistrationFeedFactory
   */
  private $external_registration_feed_factory;

  /**
   * @var ISummitAttendeeRepository
   */
  private $attendee_repository;

  /**
   * @param IExternalRegistrationFeedFactory $external_registration_feed_factory
   * @param ISummitAttendeeRepository $attendee_repository
   * @param ISummitAttendeeTicketRepository $repository
   */
  public function __construct(
    IExternalRegistrationFeedFactory $external_registration_feed_factory,
    ISummitAttendeeRepository $attendee_repository,
    ISummitAttendeeTicketRepository $ticket_repository,
  ) {
    $this->ticket_repository = $ticket_repository;
    $this->attendee_repository = $attendee_repository;
    $this->external_registration_feed_factory = $external_registration_feed_factory;
  }

  /**
   * @param Summit $summit
   * @param $ticket_criteria
   * @return ITicketFinderStrategy|null
   * @throws ValidationException
   */
  public function build(Summit $summit, $ticket_criteria): ?ITicketFinderStrategy {
    $registrationFeedType = $summit->getExternalRegistrationFeedType();
    Log::debug(
      sprintf(
        "TicketFinderStrategyFactory::build summit %s ticket_criteria %s registrationFeedType %s",
        $summit->getId(),
        $ticket_criteria,
        $registrationFeedType,
      ),
    );

    if (is_null($ticket_criteria)) {
      return null;
    }

    if (is_numeric($ticket_criteria)) {
      Log::debug(
        sprintf(
          "TicketFinderStrategyFactory::build summit %s ticket_criteria %s using TicketFinderByIdStrategy",
          $summit->getId(),
          $ticket_criteria,
        ),
      );

      return new TicketFinderByIdStrategy(
        $this->ticket_repository,
        $summit,
        intval($ticket_criteria),
      );
    }

    // check if its base 64 encoded
    if (is_string($ticket_criteria)) {
      if (base64_decode(strval($ticket_criteria), true) !== false) {
        // its a QR code on base 64
        // get by qr code
        $qr_code_content = base64_decode(strval($ticket_criteria), true);

        Log::debug(
          sprintf(
            "TicketFinderStrategyFactory::build summit %s ticket_criteria %s using TicketFinderByQRCodeStrategy",
            $summit->getId(),
            $qr_code_content,
          ),
        );

        try {
          // check first for encryption ...
          if (
            !str_starts_with($qr_code_content, $summit->getTicketQRPrefix()) &&
            !str_starts_with($qr_code_content, $summit->getBadgeQRPrefix()) &&
            $summit->hasQRCodesEncKey()
          ) {
            Log::debug(
              sprintf(
                "TicketFinderStrategyFactory::build summit %s ticket_criteria %s using TicketFinderByQRCodeStrategy with encryption",
                $summit->getId(),
                $qr_code_content,
              ),
            );

            $qr_code_content = AES::decrypt(
              $summit->getQRCodesEncKey(),
              $qr_code_content,
            )->getData();
          }

          if (str_starts_with($qr_code_content, $summit->getTicketQRPrefix())) {
            Log::debug(
              sprintf(
                "TicketFinderStrategyFactory::build summit %s ticket_criteria %s using TicketFinderByQRCodeStrategy with ticket prefix",
                $summit->getId(),
                $qr_code_content,
              ),
            );

            $fields = SummitAttendeeTicket::parseQRCode($qr_code_content);
            $prefix = $fields["prefix"];
            if ($summit->getTicketQRPrefix() != $prefix) {
              throw new ValidationException(
                sprintf(
                  "%s QR CODE is not valid for summit %s QR TICKET PREFIX %s",
                  $qr_code_content,
                  $summit->getId(),
                  $summit->getTicketQRPrefix(),
                ),
              );
            }
          }

          if (str_starts_with($qr_code_content, $summit->getBadgeQRPrefix())) {
            Log::debug(
              sprintf(
                "TicketFinderStrategyFactory::build summit %s ticket_criteria %s using TicketFinderByQRCodeStrategy with badge prefix",
                $summit->getId(),
                $qr_code_content,
              ),
            );

            $fields = SummitAttendeeBadge::parseQRCode($qr_code_content);
            $prefix = $fields["prefix"];
            if ($summit->getBadgeQRPrefix() != $prefix) {
              throw new ValidationException(
                sprintf(
                  "%s QR CODE is not valid for summit %s QR BADGE PREFIX %s",
                  $qr_code_content,
                  $summit->getId(),
                  $summit->getBadgeQRPrefix(),
                ),
              );
            }
          }

          if (!isset($fields["ticket_number"])) {
            Log::warning(
              sprintf(
                "TicketFinderStrategyFactory::build summit %s ticket_criteria %s using TicketFinderByQRCodeStrategy ticket_number is missing",
                $summit->getId(),
                $qr_code_content,
              ),
            );

            throw new ValidationException("ticket_number is missing");
          }

          $ticket_number = $fields["ticket_number"];
          $ticket_attendee_email = $fields["ticket_attendee_email"] ?? null;

          Log::debug(
            sprintf(
              "TicketFinderStrategyFactory::build summit %s ticket_criteria %s (%s)using TicketFinderByNumberStrategy",
              $summit->getId(),
              $ticket_number,
              $ticket_attendee_email,
            ),
          );

          return new TicketFinderByNumberStrategy(
            $this->ticket_repository,
            $summit,
            $ticket_number,
            $ticket_attendee_email,
          );
        } catch (ValidationException $ex) {
          Log::warning($ex);
          Log::debug(
            sprintf(
              "TicketFinderStrategyFactory::build summit %s ticket_criteria %s using TicketFinderByExternalFeedStrategy",
              $summit->getId(),
              $qr_code_content,
            ),
          );

          // is not a valid Native QR code , check if we have set any registration external feed

          if (
            !empty($registrationFeedType) &&
            $registrationFeedType !== ISummitExternalRegistrationFeedType::NoneType
          ) {
            $feed = $this->external_registration_feed_factory->build($summit);
            if (is_null($feed)) {
              return null;
            }

            if (!$feed->isValidQRCode($qr_code_content)) {
              throw $ex;
            }

            return new TicketFinderByExternalFeedStrategy(
              App::make(IRegistrationIngestionService::class),
              $feed,
              $this->attendee_repository,
              $this->ticket_repository,
              $summit,
              $qr_code_content,
            );
          }
        }
      } elseif (
        filter_var($ticket_criteria, FILTER_VALIDATE_EMAIL) &&
        $registrationFeedType !== ISummitExternalRegistrationFeedType::NoneType
      ) {
        $feed = $this->external_registration_feed_factory->build($summit);
        if (is_null($feed)) {
          return null;
        }
        return new TicketFinderByExternalFeedStrategy(
          App::make(IRegistrationIngestionService::class),
          $feed,
          $this->attendee_repository,
          $this->ticket_repository,
          $summit,
          $ticket_criteria,
        );
      }
      return new TicketFinderByNumberStrategy($this->ticket_repository, $summit, $ticket_criteria);
    }
    return null;
  }
}

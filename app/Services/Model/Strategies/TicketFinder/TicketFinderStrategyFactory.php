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

use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeedFactory;
use App\Services\Model\IRegistrationIngestionService;
use App\Services\Model\Strategies\TicketFinder\Strategies\TicketFinderByExternalFeedStrategy;
use App\Services\Model\Strategies\TicketFinder\Strategies\TicketFinderByIdStrategy;
use App\Services\Model\Strategies\TicketFinder\Strategies\TicketFinderByNumberStrategy;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\Summit;
use models\summit\SummitAttendeeBadge;
use Illuminate\Support\Facades\App;
/**
 * Class TicketFinderStrategyFactory
 * @package App\Services\Model\Strategies\TicketFinder
 */
final class TicketFinderStrategyFactory
    implements ITicketFinderStrategyFactory
{
    private $repository;

    /**
     * @var IExternalRegistrationFeedFactory
     */
    private $external_registration_feed_factory;

    /**
     * @param IExternalRegistrationFeedFactory $external_registration_feed_factory
     * @param ISummitAttendeeTicketRepository $repository
     */
    public function __construct
    (
        IExternalRegistrationFeedFactory $external_registration_feed_factory,
        ISummitAttendeeTicketRepository $repository
    )
    {
        $this->repository = $repository;
        $this->external_registration_feed_factory = $external_registration_feed_factory;
    }

    /**
     * @param Summit $summit
     * @param $ticket_criteria
     * @return ITicketFinderStrategy|null
     * @throws ValidationException
     */
    public function build(Summit $summit, $ticket_criteria): ?ITicketFinderStrategy
    {
        Log::debug(sprintf("TicketFinderStrategyFactory::build summit %s ticket_criteria %s", $summit->getId(), $ticket_criteria));
        if(is_null($ticket_criteria)) return null;
        if(is_numeric($ticket_criteria)) {
            Log::debug
            (
                sprintf
                (
                    "TicketFinderStrategyFactory::build summit %s ticket_criteria %s using TicketFinderByIdStrategy",
                    $summit->getId(),
                    $ticket_criteria
                )
            );

            return new TicketFinderByIdStrategy
            (
                $this->repository,
                $summit,
                intval($ticket_criteria)
            );
        }
        // check if its base 64 encoded
        if (is_string($ticket_criteria)) {
            if(base64_decode(strval($ticket_criteria), true) !== false){
                // its a QR code on base 64
                // get by qr code
                $qr_code_content = base64_decode(strval($ticket_criteria), true);
                try {
                    $fields = SummitAttendeeBadge::parseQRCode($qr_code_content);
                    $prefix = $fields['prefix'];
                    if ($summit->getBadgeQRPrefix() != $prefix)
                        throw new ValidationException
                        (
                            sprintf
                            (
                                "%s QR CODE is not valid for summit %s.",
                                $qr_code_content,
                                $summit->getId()
                            )
                        );

                    $ticket_number = $fields['ticket_number'];
                    return new TicketFinderByNumberStrategy
                    (
                        $this->repository,
                        $summit,
                        $ticket_number
                    );
                }
                catch(ValidationException $ex){
                    // is not a valid Native QR code , check if we have set any registration external feed
                    $external_feed = $summit->getExternalRegistrationFeedType();
                    if(!empty($external_feed)){
                        $feed = $this->external_registration_feed_factory->build($summit);
                        if(is_null($feed)) return null;

                        if(!$feed->isValidQRCode($qr_code_content))
                            throw $ex;

                        return new TicketFinderByExternalFeedStrategy
                        (
                            App::make(IRegistrationIngestionService::class),
                            $feed,
                            $this->repository,
                            $summit,
                            $qr_code_content
                        );
                    }

                }
            }
            return new TicketFinderByNumberStrategy
            (
                $this->repository,
                $summit,
                $ticket_criteria
            );
        }
        return null;
    }
}
<?php namespace App\Services\Model;
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

use App\Services\Apis\ExternalScheduleFeeds\IExternalScheduleFeedFactory;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\ValidationException;
use models\main\Affiliation;
use models\main\IMemberRepository;
use models\main\IOrganizationRepository;
use models\main\ITagRepository;
use models\main\Member;
use models\main\Organization;
use models\main\Tag;
use models\summit\IPresentationType;
use models\summit\ISpeakerRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\Presentation;
use models\summit\PresentationCategory;
use models\summit\PresentationSpeaker;
use models\summit\PresentationType;
use models\summit\Summit;
use models\summit\SummitVenueRoom;
use Exception;

/**
 * Class ScheduleIngestionService
 * @package App\Services\Model
 */
final class ScheduleIngestionService
    extends AbstractService implements IScheduleIngestionService
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IExternalScheduleFeedFactory
     */
    private $feed_factory;

    /**
     * @var ISpeakerRepository
     */
    private $speaker_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var IOrganizationRepository
     */
    private $org_repository;

    /**
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * @var ITagRepository
     */
    private $tag_repository;


    public function __construct
    (
        ISummitRepository $summit_repository,
        IMemberRepository $member_repository,
        ISpeakerRepository $speaker_repository,
        IOrganizationRepository $org_repository,
        ISummitEventRepository $event_repository,
        IExternalScheduleFeedFactory $feed_factory,
        ITagRepository $tag_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->member_repository = $member_repository;
        $this->summit_repository = $summit_repository;
        $this->org_repository = $org_repository;
        $this->speaker_repository = $speaker_repository;
        $this->event_repository = $event_repository;
        $this->tag_repository = $tag_repository;
        $this->feed_factory = $feed_factory;
    }

    /**
     * @throws \Exception
     */
    public function ingestAllSummits(): void
    {
        $this->tx_service->transaction(function () {
            foreach ($this->summit_repository->getActivesWithExternalFeed() as $summit) {
                try {
                    $processedExternalIds = $this->ingestSummit($summit);
                    foreach ($summit->getPublishedPresentations() as $presentation) {
                        if ($presentation instanceof Presentation && !empty($presentation->getExternalId()) && !in_array($presentation->getExternalId(), $processedExternalIds))
                            $this->event_repository->delete($presentation);
                    }
                } catch (Exception $ex) {
                    Log::error(sprintf("error external feed for summit id %", $summit->getId()));
                    Log::error($ex);
                }
            }
        });
    }

    /**
     * @param Summit $summit
     * @return array
     * @throws \Exception
     */
    public function ingestSummit(Summit $summit): array
    {

        return $this->tx_service->transaction(function () use ($summit) {
            $processedExternalIds = [];

            try {
                if (!$summit->isActive())
                    throw new ValidationException(sprintf("summit %s is not active!", $summit->getId()));
                $feed = $this->feed_factory->build($summit);
                if (is_null($feed))
                    throw new \InvalidArgumentException("invalid feed");

                $mainVenues = $summit->getMainVenues();
                if (count($mainVenues) == 0)
                    throw new ValidationException(sprintf("summit %s does not has a main venue set!", $summit->getId()));
                // get first as default
                $mainVenue = $mainVenues[0];

                if (is_null($summit->getBeginDate()) || is_null($summit->getEndDate()))
                    throw new ValidationException(sprintf("summit %s does not has set begin date/end date", $summit->getId()));

                if (is_null($summit->getTimeZone()))
                    throw new ValidationException(sprintf("summit %s does not has set a valid time zone", $summit->getId()));

                $events = $feed->getEvents();
                $speakers = $feed->getSpeakers();

                // get presentation type from summit
                $presentationType = $summit->getEventTypeByType(IPresentationType::Presentation);
                if (is_null($presentationType)) {
                    // create it
                    $presentationType = new PresentationType();
                    $presentationType->setType(IPresentationType::Presentation);
                    $presentationType->setMaxSpeakers(10);
                    $presentationType->setMinSpeakers(0);
                    $presentationType->setMaxModerators(1);
                    $presentationType->setMinModerators(0);
                    $summit->addEventType($presentationType);
                }

                $trackStorage = [];
                $locationStorage = [];
                $affiliationStorage = [];

                foreach ($events as $event) {

                    try {

                        // track

                        $track = $summit->getPresentationCategoryByTitle($event['track']);
                        if (is_null($track) && isset($trackStorage[$event['track']]))
                            $track = $trackStorage[$event['track']];

                        if (is_null($track)) {
                            $track = new PresentationCategory();
                            $track->setTitle($event['track']);
                            $summit->addPresentationCategory($track);
                            $trackStorage[$event['track']] = $track;
                        }

                        // location
                        $location = null;
                        if (isset($event['location'])) {
                            $location = $summit->getLocationByName($event['location']);
                            if (is_null($location) && isset($locationStorage[$event['location']]))
                                $location = $locationStorage[$event['location']];
                            if (is_null($location)) {
                                $location = new SummitVenueRoom();
                                $location->setName($event['location']);
                                $mainVenue->addRoom($location);
                                $locationStorage[$event['location']] = $location;
                            }
                        }

                        // speakers
                        $presentationSpeakers = [];
                        if (isset($event['speakers'])) {
                            foreach ($event['speakers'] as $speakerFullName) {
                                $speakerFullNameParts = explode(" ", $speakerFullName);
                                $speakerFirstName = trim(trim(array_pop($speakerFullNameParts)));
                                $speakerLastName = trim(implode(" ", $speakerFullNameParts));

                                $foundSpeaker = isset($speakers[$speakerFullName]) ? $speakers[$speakerFullName] : null;
                                $speakerEmail = $foundSpeaker && isset($foundSpeaker['email']) ? $foundSpeaker['email'] : null;
                                $companyName = $foundSpeaker && isset($foundSpeaker['company']) ? $foundSpeaker['company'] : null;
                                $companyPosition = $foundSpeaker && isset($foundSpeaker['position']) ? $foundSpeaker['position'] : null;;
                                $speakerTitle = !empty($companyName) && !empty($companyPosition) ? sprintf("%s, %s", $companyName, $companyPosition) : null;
                                // member
                                $member = !empty($speakerEmail) ? $this->member_repository->getByEmail($speakerEmail) : $this->member_repository->getByFullName($speakerFullName);

                                if (is_null($member)) {
                                    $member = new Member();
                                    $member->setEmail($speakerEmail);
                                    $member->setFirstName($speakerFirstName);
                                    $member->setLastName($speakerLastName);
                                    $this->member_repository->add($member, true);
                                }

                                // check affiliations
                                if (!empty($companyName)) {
                                    $affiliation = $member->getAffiliationByOrgName($companyName);
                                    if (is_null($affiliation) && isset($affiliationStorage[sprintf("%s_%s", $member->getId(), $companyName)]))
                                        $affiliation = $affiliationStorage[sprintf("%s_%s", $member->getId(), $companyName)];

                                    if (is_null($affiliation)) {
                                        $affiliation = new Affiliation();
                                        $org = $this->org_repository->getByName($companyName);
                                        if (is_null($org)) {
                                            $org = new Organization();
                                            $org->setName($companyName);
                                            $this->org_repository->add($org, true);
                                        }
                                        $affiliation->setOrganization($org);
                                        $affiliation->setIsCurrent(true);
                                        $member->addAffiliation($affiliation);
                                        $affiliationStorage[sprintf("%s_%s", $member->getId(), $companyName)] = $affiliation;
                                    }
                                }

                                // speaker
                                $speaker = $this->speaker_repository->getByFullName($speakerFullName);

                                if (is_null($speaker)) {
                                    $speaker = new PresentationSpeaker();
                                    $speaker->setFirstName($speakerFirstName);
                                    $speaker->setLastName($speakerLastName);
                                    $speaker->setTitle($speakerTitle);
                                    $speaker->setMember($member);
                                    $this->speaker_repository->add($speaker, true);
                                }

                                $presentationSpeakers[] = $speaker;
                            }
                        }

                        $presentation = $summit->getEventByExternalId($event['external_id']);
                        if (is_null($presentation)) {
                            $presentation = new Presentation();
                            $summit->addEvent($presentation);
                        }

                        $presentation->setType($presentationType);
                        $presentation->setCategory($track);
                        $presentation->setExternalId($event['external_id']);
                        $presentation->setLocation($location);
                        $presentation->setTitle($event['title']);
                        $presentation->setAbstract($event['abstract']);

                        // epoch local time
                        $start_datetime = $event['start_date'];
                        $end_datetime = $event['end_date'];
                        $start_datetime = new \DateTime("@$start_datetime");
                        $end_datetime = new \DateTime("@$end_datetime");
                        $start_datetime->setTimezone($summit->getTimeZone());
                        $end_datetime->setTimezone($summit->getTimeZone());
                        $presentation->setStartDate($start_datetime);
                        $presentation->setEndDate($end_datetime);

                        if (count($presentationSpeakers) > 0) {
                            $presentation->clearSpeakers();
                            foreach ($presentationSpeakers as $presentationSpeaker)
                                $presentation->addSpeaker($presentationSpeaker);
                        }
                        if (isset($event['tags'])) {
                            $presentation->clearTags();
                            foreach ($event['tags'] as $tagValue) {
                                $tag = $this->tag_repository->getByTag($tagValue);
                                if (is_null($tag)) {
                                    $tag = new Tag($tagValue);
                                    $this->tag_repository->add($tag, true);
                                }
                                $presentation->addTag($tag);
                            }
                        }
                        if (!$presentation->isPublished())
                            $presentation->publish();

                        $processedExternalIds[] = $event['external_id'];
                    } catch (Exception $ex) {
                        Log::warning(sprintf("error external feed for summit id %", $summit->getId()));
                        Log::warning($ex);
                    }
                }
            } catch (Exception $ex) {
                Log::warning(sprintf("error external feed for summit id %", $summit->getId()));
                Log::warning($ex);
            }

            return $processedExternalIds;
        });
    }
}
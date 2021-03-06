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

use App\Models\Foundation\Summit\Repositories\ISummitLocationRepository;
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

    /**
     * @var ISummitLocationRepository
     */
    private $location_repository;

    /**
     * ScheduleIngestionService constructor.
     * @param ISummitRepository $summit_repository
     * @param IMemberRepository $member_repository
     * @param ISpeakerRepository $speaker_repository
     * @param IOrganizationRepository $org_repository
     * @param ISummitEventRepository $event_repository
     * @param ISummitLocationRepository $location_repository
     * @param IExternalScheduleFeedFactory $feed_factory
     * @param ITagRepository $tag_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IMemberRepository $member_repository,
        ISpeakerRepository $speaker_repository,
        IOrganizationRepository $org_repository,
        ISummitEventRepository $event_repository,
        ISummitLocationRepository $location_repository,
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
        $this->location_repository = $location_repository;
    }

    /**
     * @throws \Exception
     */
    public function ingestAllSummits(): void
    {

        $summits = $this->tx_service->transaction(function () {
            return $this->summit_repository->getWithExternalFeed();
        });

        foreach ($summits as $summit) {

            $processedExternalIds = $this->ingestSummit($summit);

            Log::debug(sprintf( "ScheduleIngestionService::ingestAllSummits trying to get all events to delete for summit %s...", $summit->getId()));

            $summit_events = $this->tx_service->transaction(function () use ($summit, $processedExternalIds) {
                return $this->event_repository->getPublishedEventsBySummitNotInExternalIds($summit, $processedExternalIds);
            });

            //Log::debug(sprintf( "ScheduleIngestionService::ingestAllSummits got %s events to delete for summit %s...", $summit_events->count(), $summit->getId()));

            foreach ($summit_events as $summit_event) {
                $this->tx_service->transaction(function () use ($summit_event) {
                    try {
                        Log::debug(sprintf( "ScheduleIngestionService::ingestAllSummits trying to delete event %s...", $summit_event->getId()));
                        $this->event_repository->delete($summit_event);
                    } catch (Exception $ex) {
                        Log::error($ex);
                    }

                });
            }
        }
    }

    /**
     * @param Summit $summit
     * @return array
     * @throws \Exception
     */
    public function ingestSummit(Summit $summit): array
    {

        $processedExternalIds = [];
        $locations_pool = [];
        $speaker_pool = [];
        $tracks_pool = [];

        try {
            $start = time();
            $summit_id = $summit->getId();
            Log::debug(sprintf("ScheduleIngestionService::ingestSummit:: ingesting summit %s", $summit->getId()));
            $feed = $this->feed_factory->build($summit);
            if (is_null($feed))
                throw new \InvalidArgumentException("invalid feed");

            $this->tx_service->transaction(function () use ($summit_id) {
                $summit = $this->summit_repository->getById($summit_id);
                $mainVenues = $summit->getMainVenues();
                if ($mainVenues->count() == 0)
                    throw new ValidationException(sprintf("summit %s does not has a main venue set!", $summit->getId()));

                if (is_null($summit->getBeginDate()) || is_null($summit->getEndDate()))
                    throw new ValidationException(sprintf("summit %s does not has set begin date/end date", $summit->getId()));

                if (is_null($summit->getTimeZone()))
                    throw new ValidationException(sprintf("summit %s does not has set a valid time zone", $summit->getId()));

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
            });
            $events = $feed->getEvents();
            $speakers = $feed->getSpeakers();

            foreach ($events as $event) {

                try {

                    $external_id = $this->tx_service->transaction(function () use ($summit_id, $event, $speakers) {

                        Log::debug(sprintf("processing event %s - %s for summit %s", $event['external_id'], $event['title'], $summit_id));
                        // get first as default
                        $summit = $this->summit_repository->getById($summit_id);
                        if (is_null($summit) || !$summit instanceof Summit) return null;
                        $mainVenues = $summit->getMainVenues();

                        if ($mainVenues->count() == 0)
                            throw new ValidationException(sprintf("summit %s does not has a main venue set!", $summit->getId()));

                        $mainVenue = $mainVenues->first();

                        if(is_null($mainVenue))
                            throw new ValidationException(sprintf("summit %s does not has a main venue set!", $summit->getId()));

                        $presentationType = $summit->getEventTypeByType(IPresentationType::Presentation);
                        $track_title = $event['track'];
                        if (empty($track_title)) $track_title = 'TBD';
                        $track_title = str_limit($track_title, 255);
                        $track = $summit->getPresentationCategoryByTitle($track_title);

                        if (is_null($track)) {
                            $track = new PresentationCategory();
                            $track->setTitle($track_title);
                            $summit->addPresentationCategory($track);
                        }

                        // location
                        $location = null;
                        if (isset($event['location'])) {
                            $location = $mainVenue->getRoomByName($event['location']);
                            if (is_null($location)) {
                                $location = new SummitVenueRoom();
                                $location->setName(trim($event['location']));
                                $mainVenue->addRoom($location);
                            }
                        }

                        // speakers
                        $presentationSpeakers = [];
                        if (isset($event['speakers'])) {
                            foreach ($event['speakers'] as $speakerFullName) {
                                $speakerFullNameParts = explode(" ", $speakerFullName);
                                $speakerLastName = trim(trim(array_pop($speakerFullNameParts)));
                                $speakerFirstName = trim(implode(" ", $speakerFullNameParts));

                                Log::debug(sprintf("processing event %s - %s for summit %s - speaker %s", $event['external_id'], $event['title'], $summit_id, $speakerFullName));

                                $foundSpeaker = isset($speakers[$speakerFullName]) ? $speakers[$speakerFullName] : null;
                                if (is_null($foundSpeaker)) {
                                    // partial match
                                    $result_array = preg_grep("/{$speakerFullName}/i", array_keys($speakers));
                                    if (count($result_array) > 0) {
                                        $foundSpeaker = $speakers[array_values($result_array)[0]];
                                    }
                                }

                                $speakerEmail = $foundSpeaker && isset($foundSpeaker['email']) ? $foundSpeaker['email'] : null;

                                Log::debug(sprintf("ScheduleIngestionService::ingestSummit event %s - %s for summit %s speakerEmail %s", $event['external_id'], $event['title'], $summit_id, $speakerEmail ));

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

                                $member->setEmail($speakerEmail);
                                $member->setFirstName($speakerFirstName);
                                $member->setLastName($speakerLastName);

                                // check affiliations
                                if (!empty($companyName)) {
                                    $affiliation = $member->getAffiliationByOrgName($companyName);

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
                                    }
                                }

                                // speaker
                                $speaker = $this->speaker_repository->getByFullName($speakerFullName);
                                if(!is_null($speaker)){
                                    // we got an existent speaker for that full name, check if has a member assigned
                                    if($speaker->hasMember() && $speaker->getMemberId() !== $member->getId()){
                                        // speaker already belongs to another user, so we need to create a new one
                                        Log::debug(sprintf("ScheduleIngestionService::ingestSummit speaker alredy belongs to another user (%s) creating a new one", $speaker->getMemberId()));
                                        $speaker = null;
                                    }
                                }

                                if (is_null($speaker)) {
                                    $speaker = new PresentationSpeaker();
                                    $speaker->setFirstName($speakerFirstName);
                                    $speaker->setLastName($speakerLastName);
                                    $speaker->setTitle($speakerTitle);
                                    $speaker->setMember($member);
                                    $this->speaker_repository->add($speaker, true);
                                }

                                $speaker->setFirstName($speakerFirstName);
                                $speaker->setLastName($speakerLastName);
                                $speaker->setTitle($speakerTitle);
                                $speaker->setMember($member);

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

                        return $event['external_id'];
                    });
                    if (!is_null($external_id))
                        $processedExternalIds[] = $external_id;

                } catch (Exception $ex) {
                    Log::warning(sprintf("error external feed for summit id %s", $summit->getId()));
                    Log::warning($ex);
                }
            }
            $end = time();
            $delta = $end - $start;
            log::debug(sprintf("ScheduleIngestionService::ingestSummit execution call %s seconds - summit %s", $delta, $summit->getId()));

        } catch (Exception $ex) {
            Log::warning(sprintf("error external feed for summit id %s", $summit->getId()));
            Log::warning($ex);
        }

        return $processedExternalIds;

    }
}
<?php namespace App\Services\Model;
/**
 * Copyright 2017 OpenStack Foundation
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
use App\Services\Apis\CalendarSync\Exceptions\RateLimitExceededException;
use App\Services\Apis\CalendarSync\Exceptions\RevokedAccessException;
use App\Services\Apis\CalendarSync\ICalendarSyncRemoteFacadeFactory;
use CalDAVClient\Facade\Exceptions\ForbiddenException;
use CalDAVClient\Facade\Exceptions\NotFoundResourceException;
use CalDAVClient\Facade\Exceptions\ServerErrorException;
use models\summit\CalendarSync\CalendarSyncInfo;
use models\summit\CalendarSync\WorkQueue\AbstractCalendarSyncWorkRequest;
use models\summit\CalendarSync\WorkQueue\MemberCalendarScheduleSummitActionSyncWorkRequest;
use models\summit\CalendarSync\WorkQueue\MemberEventScheduleSummitActionSyncWorkRequest;
use models\summit\CalendarSync\WorkQueue\MemberScheduleSummitActionSyncWorkRequest;
use models\summit\IAbstractCalendarSyncWorkRequestRepository;
use models\summit\ICalendarSyncInfoRepository;
use utils\PagingInfo;
use libs\utils\ITransactionService;
use Illuminate\Support\Facades\Log;
use Exception;
/**
 * Class MemberActionsCalendarSyncProcessingService
 * @package App\Services\Model
 */
final class MemberActionsCalendarSyncProcessingService
implements IMemberActionsCalendarSyncProcessingService
{
    const FailedAddSummitEventTxFileFormatName = '/tmp/failed_insert_member_%s_calendar_%s_summit_event_%s.json';
    /**
     * @var IAbstractCalendarSyncWorkRequestRepository
     */
    private $work_request_repository;

    /**
     * @var ICalendarSyncInfoRepository
     */
    private $calendar_sync_repository;

    /**
     * @var ITransactionService
     */
    private $tx_manager;

    /**
     * @var ICalendarSyncWorkRequestPreProcessor
     */
    private $preprocessor_requests;

    /**
     * @var ICalendarSyncRemoteFacadeFactory
     */
    private $facade_factory;

    /**
     * MemberActionsCalendarSyncProcessingService constructor.
     * @param IAbstractCalendarSyncWorkRequestRepository $work_request_repository
     * @param ICalendarSyncInfoRepository $calendar_sync_repository
     * @param ICalendarSyncWorkRequestPreProcessor $preprocessor_requests
     * @param ICalendarSyncRemoteFacadeFactory $facade_factory
     * @param ITransactionService $tx_manager
     */
    public function __construct
    (
        IAbstractCalendarSyncWorkRequestRepository $work_request_repository,
        ICalendarSyncInfoRepository $calendar_sync_repository,
        ICalendarSyncWorkRequestPreProcessor $preprocessor_requests,
        ICalendarSyncRemoteFacadeFactory $facade_factory,
        ITransactionService $tx_manager
    )
    {
        $this->work_request_repository           = $work_request_repository;
        $this->calendar_sync_repository          = $calendar_sync_repository;
        $this->preprocessor_requests             = $preprocessor_requests;
        $this->facade_factory                    = $facade_factory;
        $this->tx_manager                        = $tx_manager;
    }

    /**
     * @param CalendarSyncInfo|null $calendar_sync_info
     */
    private function sendReSyncCalendarEmail(CalendarSyncInfo $calendar_sync_info){
        if(!is_null($calendar_sync_info) && !$calendar_sync_info->isRevoked()){
            // revoke it ...
            $calendar_sync_info->setRevoked(true);
        }
    }
    /**
     * @param string $provider
     * @param int $batch_size
     * @return int|mixed
     * @throws Exception
     */
    public function processActions($provider = 'ALL', $batch_size = 1000)
    {
        return $this->tx_manager->transaction(function() use($provider, $batch_size){
            $count          = 0;

            $res = $this->work_request_repository->getUnprocessedMemberScheduleWorkRequestActionByPage
            (
                new PagingInfo(1, $batch_size),
                $provider
            );

            $requests = $this->preprocessor_requests->preProcessActions($res->getItems());
            log::info(sprintf("provider %s got %s request to process ...", $provider, count($requests)));

            foreach ($requests as $request){
                try {
                    log::debug(sprintf("iteration # %s", $count+1));
                    if (!$request instanceof MemberScheduleSummitActionSyncWorkRequest) continue;
                    $calendar_sync_info   = $request->getCalendarSyncInfo();
                    $remote_facade        = $this->facade_factory->build($calendar_sync_info);
                    if (is_null($remote_facade)) continue;
                    $member               = $request->getOwner();
                    $request_type         = $request->getType();
                    $request_sub_type     = $request->getSubType();

                    try {
                        log::info(sprintf
                        (
                            "%s - processing work request %s - sub type %s - type %s - member %s - credential id %s -revoked credentials %s",
                            $provider,
                            $request->getIdentifier(),
                            $request_sub_type,
                            $request_type,
                            $member->getIdentifier(),
                            $calendar_sync_info->getId(),
                            $calendar_sync_info->isRevoked() ? 1 : 0
                        ));
                    }
                    catch (Exception $ex8){
                        Log::warning($ex8);
                        continue;
                    }

                    switch ($request_sub_type) {

                        case MemberEventScheduleSummitActionSyncWorkRequest::SubType: {

                            $summit_event_id = $request->getSummitEventId();
                            $summit_event    = $request->getSummitEvent();

                            log::info(sprintf
                            (
                                "%s - processing work request %s - sub type %s - type %s - event id %s - member %s - credential id %s -revoked credentials %s",
                                $provider,
                                $request->getIdentifier(),
                                $request_sub_type,
                                $request_type,
                                $summit_event_id,
                                $member->getIdentifier(),
                                $calendar_sync_info->getId(),
                                $calendar_sync_info->isRevoked()? 1:0
                            ));

                            switch ($request_type) {
                                case AbstractCalendarSyncWorkRequest::TypeAdd:
                                        if(is_null($summit_event)){
                                            Log::warning(sprintf("EVENT ADD : event id %s does not exists!", $summit_event_id, $member->getId()));
                                            break;
                                        }
                                        if ($calendar_sync_info->isRevoked()){
                                            Log::warning(sprintf("EVENT ADD : event id %s - member id %s could not be added on external calendar bc credential are revoked!", $summit_event_id, $member->getId()));
                                            break;
                                        }
                                        if($member->isEventSynchronized($calendar_sync_info, $summit_event_id)){
                                            Log::warning(sprintf("EVENT ADD : event id %s - member id %s already synchronized", $summit_event_id, $member->getId()));
                                            break;
                                        }
                                        $schedule_sync_info = $remote_facade->addEvent($request);
                                        if(is_null($schedule_sync_info)){
                                            Log::warning(sprintf("EVENT ADD : event id %s - member id %s could not be added on external calendar", $summit_event_id, $member->getId()));
                                            break;
                                        }
                                        $member->add2ScheduleSyncInfo($schedule_sync_info);
                                    break;
                                case AbstractCalendarSyncWorkRequest::TypeUpdate:
                                    if(is_null($summit_event)){
                                        Log::warning(sprintf("EVENT UPDATE : event id %s does not exists!", $summit_event_id, $member->getId()));
                                        break;
                                    }
                                    if($calendar_sync_info->isRevoked()) break;
                                    $sync_info    = $member->getScheduleSyncInfoByEvent($summit_event_id, $calendar_sync_info);
                                    $is_scheduled = $member->isOnSchedule($summit_event);
                                    if(is_null($sync_info)) break;
                                    if(!$is_scheduled) {
                                        $member->removeFromScheduleSyncInfo($sync_info);
                                        break;
                                    }
                                    $remote_facade->updateEvent($request, $sync_info);
                                    break;
                                case AbstractCalendarSyncWorkRequest::TypeRemove:
                                    if($calendar_sync_info->isRevoked()) break;
                                    $schedule_sync_info = $member->getScheduleSyncInfoByEvent($summit_event_id, $calendar_sync_info);
                                    if(is_null($schedule_sync_info)){
                                        Log::warning(sprintf("EVENT REMOVE : event id %s - member id %s could not be removed, schedule synch info is null", $summit_event_id, $member->getId()));
                                        break;
                                    }
                                    $remote_facade->deleteEvent($request, $schedule_sync_info);
                                    $member->removeFromScheduleSyncInfo($schedule_sync_info);
                                    break;
                            }
                        }
                        break;
                        case MemberCalendarScheduleSummitActionSyncWorkRequest::SubType: {
                            switch ($request_type) {
                                case AbstractCalendarSyncWorkRequest::TypeAdd:
                                    if($calendar_sync_info->isRevoked()){
                                        Log::warning(sprintf("CALENDAR ADD : calendar sync info id %s, member id %s is revoked!", $calendar_sync_info->getId(), $member->getId()));
                                        break;
                                    }
                                    $remote_facade->createCalendar($request, $calendar_sync_info);
                                    break;
                                case AbstractCalendarSyncWorkRequest::TypeRemove:
                                    $remote_facade->deleteCalendar($request, $calendar_sync_info);
                                    $member->removeFromCalendarSyncInfo($calendar_sync_info);
                                    break;
                            }
                        }
                        break;
                    }

                    $request->markProcessed();
                    $count++;
                    usleep($remote_facade->getSleepInterval());
                }
                catch(ForbiddenException $ex1){
                    // cant create calendar (CAL DAV)...
                    Log::warning($ex1);
                }
                catch(RevokedAccessException $ex2){
                    Log::warning($ex2);
                    $this->sendReSyncCalendarEmail($calendar_sync_info);
                }
                catch(NotFoundResourceException $ex3){
                    Log::warning($ex3);
                }
                catch(ServerErrorException $ex5){
                    Log::error($ex5);
                }
                catch (RateLimitExceededException $ex6){
                    Log::critical($ex6);
                    break;
                }
                catch(Exception $ex7){
                    Log::error($ex7);
                }
            }
            return $count;
        });
    }
}
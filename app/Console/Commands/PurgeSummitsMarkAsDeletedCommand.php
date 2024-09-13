<?php namespace App\Console\Commands;
/*
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

use App\Models\Foundation\Summit\Repositories\ISummitLocationRepository;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Models\Foundation\Summit\Repositories\ISummitRegistrationInvitationRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class PurgeSummitsMarkAsDeletedCommand
 * @package App\Console\Commands
 */
final class PurgeSummitsMarkAsDeletedCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:purge-mark-as-deleted';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:purge-mark-as-deleted {summit_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Summit Purge Mark As Deleted Command';

    /**
     * @var ISummitRepository
     */
    private $repository;

    /**
     * @var ISummitOrderRepository
     */
    private $order_repository;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @var ISummitRegistrationInvitationRepository
     */
    private $invitations_repository;

    /**
     * @var ISummitLocationRepository
     */
    private $location_repository;

    /**
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * @var ITransactionService
     */
    private $tx_service;

    public function __construct
    (
        ISummitRepository                       $repository,
        ISummitEventRepository                  $event_repository,
        ISummitLocationRepository               $location_repository,
        ISummitOrderRepository                  $order_repository,
        ISummitAttendeeRepository               $attendee_repository,
        ISummitRegistrationInvitationRepository $invitations_repository,
        ITransactionService                     $tx_service)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->event_repository = $event_repository;
        $this->order_repository = $order_repository;
        $this->attendee_repository = $attendee_repository;
        $this->invitations_repository = $invitations_repository;
        $this->location_repository = $location_repository;
        $this->tx_service = $tx_service;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info("PurgeSummitsMarkAsDeletedCommand::handle");
        Log::debug("PurgeSummitsMarkAsDeletedCommand::handle");
        try {
            $summit_id = $this->argument('summit_id');
            $ids = [];
            if (empty($summit_id)) {
                $filter = new Filter();
                $filter->addFilterCondition(FilterElement::makeEqual('mark_as_deleted', true));
                $ids = $this->repository->getAllIdsByPage(new PagingInfo(1, 10), $filter);
            } else {
                $summit = $this->repository->getById(intval($summit_id));
                if (!$summit instanceof Summit) {
                    $this->error(sprintf("Summit %s not found", $summit_id));
                    return;
                }
                if (!$summit->isDeleting()) {
                    $this->error(sprintf("Summit %s could not be deleted bc is not marked for deletion.", $summit_id));
                    return;
                }
                $ids = [$summit->getId()];
            }

            $this->info(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summits %s", json_encode($ids)));
            $count = 0;
            foreach ($ids as $id) {
                try {
                    $this->info(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s", $id));
                    Log::debug(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s", $id));
                    $start = time();
                    $this->tx_service->transaction(function () use ($id) {
                        $summit = $this->repository->getById($id);
                        if (!$summit instanceof Summit) {
                            $this->error(sprintf("Summit %s not found", $id));
                            return;
                        }
                        $this->info(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s invitations ...", $id));
                        Log::debug(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s invitations ...", $id));
                        $this->invitations_repository->deleteAllBySummit($id);
                        $this->info(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s orders ...", $id));
                        Log::debug(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s orders ...", $id));
                        $this->order_repository->deleteAllBySummit($id);
                        $this->info(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s attendees ...", $id));
                        Log::debug(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s attendees ...", $id));
                        $this->attendee_repository->deleteAllBySummit($id);
                        $this->info(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s activities ...", $id));
                        Log::debug(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s activities ...", $id));
                        $this->event_repository->deleteAllBySummit($id);
                        $this->info(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s locations ...", $id));
                        Log::debug(sprintf("PurgeSummitsMarkAsDeletedCommand::handle purging summit %s locations ...", $id));
                        $this->location_repository->deleteAllBySummit($id);
                        $this->info(sprintf("PurgeSummitsMarkAsDeletedCommand::handle deleting summit %s", $id));
                        Log::debug(sprintf("PurgeSummitsMarkAsDeletedCommand::handle deleting summit %s", $id));
                        $this->repository->delete($summit);
                    });

                    $end = time();
                    $delta = $end - $start;
                    $this->info(sprintf("PurgeSummitsMarkAsDeletedCommand::handle summit %s purged in %s seconds", $id, $delta));
                    Log::debug(sprintf("PurgeSummitsMarkAsDeletedCommand::handle summit %s purged in %s seconds", $id, $delta));
                    ++$count;

                } catch (\Exception $ex) {
                    Log::warning($ex);
                }
            }
            $this->info(sprintf("PurgeSummitsMarkAsDeletedCommand::handle %s summits purged", $count));
            Log::debug(sprintf("PurgeSummitsMarkAsDeletedCommand::handle %s summits purged", $count));

        } catch (\Exception $ex) {
            Log::error($ex);
        }
        $this->info("PurgeSummitsMarkAsDeletedCommand::handle done");
        Log::debug("PurgeSummitsMarkAsDeletedCommand::handle done");
    }
}
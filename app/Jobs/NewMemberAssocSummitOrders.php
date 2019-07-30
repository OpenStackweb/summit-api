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
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\main\IMemberRepository;
use models\main\Member;
use models\summit\ISummitAttendeeRepository;
use models\summit\SummitAttendee;
use models\summit\SummitOrder;
/**
 * Class NewMemberAssocSummitOrders
 * @package App\Jobs
 */
class NewMemberAssocSummitOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    /**
     * @var int
     */
    private $member_id;

    /**
     * NewMemberAssocSummitOrders constructor.
     * @param int $member_id
     */
    public function __construct(int $member_id)
    {
        $this->member_id = $member_id;
    }

    /**
     * @param ISummitOrderRepository $order_repository
     * @param IMemberRepository $member_repository
     * @param ISummitAttendeeRepository $attendee_repository
     * @param ITransactionService $tx_service
     * @throws \Exception
     */
    public function handle
    (
        ISummitOrderRepository $order_repository,
        IMemberRepository $member_repository,
        ISummitAttendeeRepository $attendee_repository,
        ITransactionService $tx_service
    )
    {
        $tx_service->transaction(function() use($order_repository, $member_repository, $attendee_repository){

            Log::debug(sprintf("NewMemberAssocSummitOrders::handle trying to get member id %s", $this->member_id));
            $member = $member_repository->getById($this->member_id);
            if(is_null($member) || !$member instanceof Member) return;

            // associate orders
            $orders = $order_repository->getAllByOwnerEmail($member->getEmail());
            if(!is_null($orders)) {
                foreach ($orders as $order) {
                    if (!$order instanceof SummitOrder) continue;
                    Log::debug(sprintf("NewMemberAssocSummitOrders::handle got order %s for member %s", $order->getNumber(), $this->member_id));
                    $member->addSummitRegistrationOrder($order);
                }
            }

            // associate attendees/tickets
            $attendees = $attendee_repository->getByEmail($member->getEmail());
            if(!is_null($attendees)) {
                foreach ($attendees as $attendee) {
                    if (!$attendee instanceof SummitAttendee) continue;
                    Log::debug(sprintf("NewMemberAssocSummitOrders::handle got attendee %s for member", $attendee->getId(), $this->member_id));
                    $attendee->setMember($member);
                }
            }

        });
    }
}

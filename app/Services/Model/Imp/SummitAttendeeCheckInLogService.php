<?php namespace services\model;
/**
 * Copyright 2026 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeCheckInLogRepository;
use App\Services\Model\AbstractService;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\main\Member;
use models\oauth2\IResourceServerContext;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeCheckInLog;

/**
 * Class SummitAttendeeCheckInLogService
 * @package services\model
 */
final class SummitAttendeeCheckInLogService
    extends AbstractService
    implements ISummitAttendeeCheckInLogService
{
    /**
     * @var ISummitAttendeeCheckInLogRepository
     */
    private $repository;

    /**
     * @var IResourceServerContext
     */
    private $resource_server_context;

    public function __construct
    (
        ISummitAttendeeCheckInLogRepository $repository,
        IResourceServerContext              $resource_server_context,
        ITransactionService                 $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
        $this->resource_server_context = $resource_server_context;
    }

    /**
     * @inheritDoc
     */
    public function log
    (
        SummitAttendee $attendee,
        string         $action,
        string         $source,
        ?string        $reason = null,
        ?Member        $actor = null
    ): SummitAttendeeCheckInLog
    {
        $reason = is_null($reason) ? null : trim($reason);
        if ($reason === '') $reason = null;

        if (is_null($actor))
            $actor = $this->resource_server_context->getCurrentUser(false, false);

        $request = request();

        $entry = SummitAttendeeCheckInLog::build
        (
            $attendee,
            $action,
            $source,
            $actor,
            $this->resource_server_context->getCurrentClientId(),
            $reason,
            $request?->ip(),
            $request?->userAgent()
        );

        Log::debug
        (
            sprintf
            (
                "SummitAttendeeCheckInLogService::log attendee %s action %s source %s actor %s",
                $attendee->getId(),
                $action,
                $source,
                is_null($actor) ? 'n/a' : $actor->getId()
            )
        );

        $this->repository->add($entry);

        return $entry;
    }
}

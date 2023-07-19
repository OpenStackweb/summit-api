<?php namespace App\Services\Model\Imp;
/**
 * Copyright 2020 OpenStack Foundation
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

use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\summit\Summit;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlow;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitEmailEventFlowService;
/**
 * Class SummitEmailEventFlowService
 * @package App\Services\Model\Imp
 */
final class SummitEmailEventFlowService
    extends AbstractService
    implements ISummitEmailEventFlowService
{
    public function __construct(ITransactionService $tx_service)
    {
        parent::__construct($tx_service);
    }

    /**
     * @inheritDoc
     */
    public function updateEmailEventFlow(Summit $summit, int $event_id, array $data): SummitEmailEventFlow
    {
        return $this->tx_service->transaction(function () use ($summit, $event_id, $data) {
            $event = $summit->getEmailEventById($event_id);
            if (is_null($event))
                throw new EntityNotFoundException("Email Event not found");

            $event->setEmailTemplateIdentifier(trim($data['email_template_identifier']));

            if (isset($data['recipients']) && is_array($data['recipients'])) {
                $event->setEmailRecipients($data['recipients']);
            }

            return $event;
        });
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @throws \Exception
     */
    public function deleteEmailEventFlow(Summit $summit, int $event_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $event_id) {
            $event = $summit->getEmailEventById($event_id);
            if (is_null($event))
                throw new EntityNotFoundException("Email Event  not found");

            $summit->removeEmailEventFlow($event);
        });
    }
}
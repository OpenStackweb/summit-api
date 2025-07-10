<?php namespace App\Services;
/**
 * Copyright 2025 OpenStack Foundation
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

use App\Models\Foundation\Summit\Factories\SummitSponsorshipAddOnFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitSponsorshipService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\summit\Summit;
use models\summit\SummitSponsorship;
use models\summit\SummitSponsorshipAddOn;

/**
 * Class SummitSponsorshipService
 * @package App\Services\Model
 */
final class SummitSponsorshipService extends AbstractService implements ISummitSponsorshipService
{
    /**
     * @param ITransactionService $tx_service
     */
    public function __construct(ITransactionService $tx_service)
    {
        parent::__construct($tx_service);
    }

    /**
     * @inheritDoc
     */
    public function addSponsorships(Summit $summit, int $sponsor_id, array $summit_sponsorship_type_ids): array
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $summit_sponsorship_type_ids) {
            $res = [];
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            foreach ($summit_sponsorship_type_ids as $summit_sponsorship_type_id) {
                $summit_sponsorship_type = $summit->getSummitSponsorshipTypeById($summit_sponsorship_type_id);

                $sponsorship = new SummitSponsorship();
                $sponsorship->setType($summit_sponsorship_type);
                $summit_sponsor->addSponsorship($sponsorship);
                $res[] = $sponsorship;
            }
            return $res;
        });
    }

    /**
     * @inheritDoc
     */
    public function removeSponsorship(Summit $summit, int $sponsor_id, int $sponsorship_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id, $sponsorship_id) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $sponsorship = $summit_sponsor->getSponsorshipById($sponsorship_id);
            if (is_null($sponsorship))
                throw new EntityNotFoundException("Sponsorship {$sponsorship_id} not found in sponsor {$sponsor_id}.");

            $summit_sponsor->removeSponsorship($sponsorship);
        });
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function addNewAddOn(Summit $summit, int $sponsor_id, int $sponsorship_id, array $payload): SummitSponsorshipAddOn
    {
         return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $sponsorship_id, $payload) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $sponsorship = $summit_sponsor->getSponsorshipById($sponsorship_id);
            if (is_null($sponsorship))
                throw new EntityNotFoundException("Sponsorship {$sponsorship_id} not found for sponsor {$sponsor_id}.");

            $add_on = SummitSponsorshipAddOnFactory::build($payload);
            $sponsorship->addAddOn($add_on);
            return $add_on;
        });
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function updateAddOn(Summit $summit, int $sponsor_id, int $sponsorship_id, int $add_on_id, array $payload): SummitSponsorshipAddOn
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $sponsorship_id, $add_on_id, $payload) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $sponsorship = $summit_sponsor->getSponsorshipById($sponsorship_id);
            if (is_null($sponsorship))
                throw new EntityNotFoundException("Sponsorship {$sponsorship_id} not found for sponsor {$sponsor_id}.");

            $add_on = $sponsorship->getAddOnById($add_on_id);
            if (is_null($add_on))
                throw new EntityNotFoundException("AddOn {$add_on_id} not found for sponsorship {$sponsorship_id}.");

            return SummitSponsorshipAddOnFactory::populate($add_on, $payload);
        });
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function removeAddOn(Summit $summit, int $sponsor_id, int $sponsorship_id, int $add_on_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id, $sponsorship_id, $add_on_id) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $sponsorship = $summit_sponsor->getSponsorshipById($sponsorship_id);
            if (is_null($sponsorship))
                throw new EntityNotFoundException("Sponsorship {$sponsorship_id} not found in sponsor {$sponsor_id}.");

            $add_on = $sponsorship->getAddOnById($add_on_id);
            if (is_null($add_on))
                throw new EntityNotFoundException("AddOn {$add_on_id} not found for sponsorship {$sponsorship_id}.");

            $sponsorship->removeAddOn($add_on);
        });
    }
}
<?php namespace App\Events\SponsorServices;

/*
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

use models\summit\SummitSponsorshipAddOn;

class SummitSponsorshipAddOnCreatedEventDTO
{
    private int $id;
    private int $sponsorship_id;
    private string $type;
    private string $name;

    private int $sponsor_id;

    private int $summit_id;

    public function __construct(
        int    $id,
        int    $sponsorship_id,
        string $type,
        string $name,
        int $sponsor_id,
        int $summit_id,
    )
    {
        $this->id = $id;
        $this->sponsorship_id = $sponsorship_id;
        $this->type = $type;
        $this->name = $name;
        $this->sponsor_id = $sponsor_id;
        $this->summit_id = $summit_id;
    }

    public static function fromSponsorshipAddOn(SummitSponsorshipAddOn $add_on): self
    {
        return new self(
            $add_on->getId(),
            $add_on->getSponsorship()->getId(),
            $add_on->getType(),
            $add_on->getName(),
            $add_on->getSponsorship()->getSponsor()->getId(),
            $add_on->getSponsorship()->getSponsor()->getSummitId(),
        );
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'sponsorship_id' => $this->sponsorship_id,
            'sponsor_id' => $this->sponsor_id,
            'summit_id' => $this->summit_id,
        ];
    }
}

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

use models\summit\SummitSponsorship;

class SummitSponsorshipCreatedEventDTO
{
    private int $id;
    private int $sponsor_id;
    private int $sponsorship_type_id;
    private int $summit_sponsorship_type_id;
    private string $sponsorship_type_name;

    private int $summit_id;

    public function __construct(
        int    $id,
        int    $sponsor_id,
        int    $summit_sponsorship_type_id,
        int    $sponsorship_type_id,
        string $sponsorship_type_name,
        int    $summit_id,
    )
    {
        $this->id = $id;
        $this->sponsor_id = $sponsor_id;
        $this->summit_sponsorship_type_id = $summit_sponsorship_type_id;
        $this->sponsorship_type_id = $sponsorship_type_id;
        $this->sponsorship_type_name = $sponsorship_type_name;
        $this->summit_id = $summit_id;
    }

    public static function fromSponsorship(SummitSponsorship $sponsorship): self
    {
        $summit_sponsorship_type = $sponsorship->getType();
        $sponsorship_type = $summit_sponsorship_type->getType();
        $sponsor = $sponsorship->getSponsor();

        return new self(
            $sponsorship->getId(),
            $sponsor->getId(),
            $summit_sponsorship_type->getId(),
            $sponsorship_type->getId(),
            $sponsorship_type->getName(),
            $sponsor->getSummitId()
        );
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'sponsor_id' => $this->sponsor_id,
            'summit_sponsorship_type_id' => $this->summit_sponsorship_type_id,
            'sponsorship_type_id' => $this->sponsorship_type_id,
            'sponsorship_type_name' => $this->sponsorship_type_name,
            'summit_id' => $this->summit_id,
        ];
    }
}

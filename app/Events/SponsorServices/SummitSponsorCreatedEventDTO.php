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

class SummitSponsorCreatedEventDTO
{
    private int $id;

    private int $company_id;
    private string $company_name;

    private int $summit_id;


    public function __construct(
        int    $id,
        int    $company_id,
        string $company_name,
        int    $summit_id,

    )
    {
        $this->id = $id;
        $this->company_id = $company_id;
        $this->company_name = $company_name;
        $this->summit_id = $summit_id;
    }

    public static function fromSummitSponsor($sponsor): self
    {
        return new self(
            $sponsor->getId(),
            $sponsor->getCompanyId(),
            $sponsor->getCompany()->getName(),
            $sponsor->getSummitId()
        );
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'summit_id' => $this->summit_id,
            'company_id' => $this->company_id,
            'company_name' => $this->company_name
        ];
    }
}

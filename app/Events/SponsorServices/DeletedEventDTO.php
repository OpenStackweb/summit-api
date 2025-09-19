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

class DeletedEventDTO
{
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function fromEntity($entity): self
    {
        return new self($entity->getId());
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id
        ];
    }
}
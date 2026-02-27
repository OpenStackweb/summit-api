<?php namespace App\Events\SponsorServices;

/*
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

class SummitMediaFileTypeCreatedEventDTO
{
    private int $id;
    private string $name;
    private string $description;
    private string $allowed_extensions;

    public function __construct(
        int    $id,
        string $name,
        string $description,
        string $allowed_extensions
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->allowed_extensions = $allowed_extensions;
    }

    public static function fromSummitMediaFileType($summit_media_file_type): self
    {
        return new self(
            $summit_media_file_type->getId(),
            $summit_media_file_type->getName(),
            $summit_media_file_type->getDescription(),
            $summit_media_file_type->getAllowedExtensions(),
        );
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'allowed_extensions' => $this->allowed_extensions
        ];
    }
}

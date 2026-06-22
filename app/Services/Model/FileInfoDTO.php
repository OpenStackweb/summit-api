<?php namespace App\Services\Model;
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

final class FileInfoDTO {
    public static function validationRules(): array {
        return [
            'filepath'      => 'required|string',
            'filename'      => 'required|string',
            'md5'           => 'required|string',
            'size'          => 'required|integer',
            'mime_type'     => 'sometimes|string',
            'source_bucket' => 'sometimes|string',
        ];
    }

    public function __construct(
        public readonly int $owner_entity_id,
        public readonly string $owner_entity_class,
        public readonly string $owner_member_name,
        public readonly string $filepath,
        public readonly string $filename,
        public readonly int $size,
        public readonly ?string $md5 = null,
        public readonly ?string $mime_type = null,
        public readonly ?string $source_bucket = null,
    ) {}

    public function __toString(): string {
        return json_encode(get_object_vars($this), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}

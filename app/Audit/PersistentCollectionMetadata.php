<?php

namespace App\Audit;

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

use Doctrine\ORM\PersistentCollection;

class PersistentCollectionMetadata
{
    public function __construct(
        public readonly string $fieldName,
        public readonly string $targetEntity,
        public readonly bool $isInitialized,
        public readonly array $preloadedDeletedIds,
        public readonly PersistentCollection $collection,
    ) {
    }

    public static function fromCollection(
        PersistentCollection $collection,
        array $preloadedDeletedIds = []
    ): self {
        $mapping = $collection->getMapping();

        return new self(
            fieldName: $mapping->fieldName ?? 'unknown',
            targetEntity: $mapping->targetEntity ?? 'unknown',
            isInitialized: $collection->isInitialized(),
            preloadedDeletedIds: $preloadedDeletedIds,
            collection: $collection,
        );
    }
}

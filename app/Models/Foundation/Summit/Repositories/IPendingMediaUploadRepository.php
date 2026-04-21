<?php namespace App\Models\Foundation\Summit\Repositories;
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

use models\summit\PendingMediaUpload;
use models\utils\IBaseRepository;

/**
 * Interface IPendingMediaUploadRepository
 * @package App\Models\Foundation\Summit\Repositories
 */
interface IPendingMediaUploadRepository extends IBaseRepository
{
    /**
     * Get all pending uploads ordered by creation date
     * @return PendingMediaUpload[]
     */
    public function getPendingUploads(): array;

    /**
     * Reset stuck Processing rows back to Pending
     * @param int $stale_minutes Number of minutes before a Processing row is considered stuck
     * @return int Number of rows reset
     */
    public function resetStuckProcessingRows(int $stale_minutes = 10): int;

    /**
     * Delete completed uploads older than specified days
     * @param int $days Number of days to keep completed uploads
     * @param int $limit Maximum number of rows to delete per call
     * @return int Number of rows deleted
     */
    public function deleteCompletedOlderThan(int $days = 7, int $limit = 1000): int;
}

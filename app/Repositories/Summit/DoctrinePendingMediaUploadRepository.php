<?php namespace App\Repositories\Summit;
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

use App\Models\Foundation\Summit\Repositories\IPendingMediaUploadRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use models\summit\PendingMediaUpload;

/**
 * Class DoctrinePendingMediaUploadRepository
 * @package App\Repositories\Summit
 */
final class DoctrinePendingMediaUploadRepository
    extends SilverStripeDoctrineRepository
    implements IPendingMediaUploadRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return PendingMediaUpload::class;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'status' => 'e.status',
            'summit_id' => 'e.summit_id'
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return ['id', 'created'];
    }

    /**
     * @inheritDoc
     */
    public function getPendingUploads(): array
    {
        $query = $this->getEntityManager()
            ->createQuery("SELECT p FROM models\summit\PendingMediaUpload p WHERE p.status = :status ORDER BY p.created ASC");
        $query->setParameter('status', PendingMediaUpload::STATUS_PENDING);
        return $query->getResult();
    }

    /**
     * @inheritDoc
     */
    public function resetStuckProcessingRows(int $stale_minutes = 10): int
    {
        $stale_threshold = new \DateTime('now', new \DateTimeZone(\models\utils\SilverstripeBaseModel::DefaultTimeZone));
        $stale_threshold->modify(sprintf('-%d minutes', $stale_minutes));

        $query = $this->getEntityManager()
            ->createQuery(
                "UPDATE models\summit\PendingMediaUpload p SET p.status = :pending_status WHERE p.status = :processing_status AND p.last_edited < :threshold"
            );
        $query->setParameter('pending_status', PendingMediaUpload::STATUS_PENDING);
        $query->setParameter('processing_status', PendingMediaUpload::STATUS_PROCESSING);
        $query->setParameter('threshold', $stale_threshold);

        return $query->execute();
    }

    /**
     * @inheritDoc
     */
    public function deleteCompletedOlderThan(int $days = 7, int $limit = 1000): int
    {
        $cutoff_date = new \DateTime('now', new \DateTimeZone(\models\utils\SilverstripeBaseModel::DefaultTimeZone));
        $cutoff_date->modify(sprintf('-%d days', $days));

        $query = $this->getEntityManager()
            ->createQuery(
                "DELETE FROM models\summit\PendingMediaUpload p WHERE p.status = :status AND p.processed_date < :cutoff_date"
            );
        $query->setParameter('status', PendingMediaUpload::STATUS_COMPLETED);
        $query->setParameter('cutoff_date', $cutoff_date);
        $query->setMaxResults($limit);

        return $query->execute();
    }
}

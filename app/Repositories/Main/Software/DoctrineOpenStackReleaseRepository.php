<?php namespace App\Repositories\Main\Software;
/*
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Software\OpenStackRelease;
use App\Models\Foundation\Software\Repositories\IOpenStackReleaseRepository;
use App\Repositories\SilverStripeDoctrineRepository;
use Doctrine\ORM\NoResultException;
/**
 * Class DoctrineOpenStackReleaseRepository
 * @package App\Repositories\Main\Software
 */
final class DoctrineOpenStackReleaseRepository extends SilverStripeDoctrineRepository implements
  IOpenStackReleaseRepository {
  /**
   * @return string
   */
  protected function getBaseEntity() {
    return OpenStackRelease::class;
  }

  /**
   * @return OpenStackRelease|null
   * @throws \Doctrine\ORM\NonUniqueResultException
   */
  public function getCurrent(): ?OpenStackRelease {
    try {
      return $this->getEntityManager()
        ->createQueryBuilder()
        ->select("distinct e")
        ->from($this->getBaseEntity(), "e")
        ->where("UPPER(TRIM(e.status)) = UPPER(TRIM(:status))")
        ->setParameter("status", "Current")
        ->orderBy("e.release_date", "DESC")
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
    } catch (NoResultException $e) {
      return null;
    }
  }
}

<?php namespace models\utils;
/**
 * Copyright 2015 OpenStack Foundation
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

use App\Models\Utils\BaseEntity;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use LaravelDoctrine\ORM\Facades\Registry;
use Libs\Utils\Doctrine\DoctrineStatementValueBinder;

/***
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * Class SilverstripeBaseModel
 * @package models\utils
 */
class SilverstripeBaseModel extends BaseEntity
{
    const DefaultTimeZone = 'America/Chicago';

    /**
     * @var \DateTime
     * @ORM\Column(name="Created", type="datetime", nullable=false)
     */
    protected $created;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastEdited", type="datetime", nullable=false)
     */
    protected $last_edited;

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedUTC()
    {
        if (is_null($this->created)) return null;
        return $this->getDateFromLocalToUTC($this->created);
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getLastEdited()
    {
        return $this->last_edited;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastEditedUTC()
    {
        if (is_null($this->last_edited)) return null;
        return $this->getDateFromLocalToUTC($this->last_edited);
    }

    /**
     * @param \DateTime $value
     * @return \DateTime|null
     */
    protected function getDateFromLocalToUTC(\DateTime $value)
    {
        if (is_null($value)) return null;
        $default_timezone = new \DateTimeZone(self::DefaultTimeZone);
        $utc_timezone = new \DateTimeZone("UTC");
        $timestamp = $value->format('Y-m-d H:i:s');
        $local_date = new \DateTime($timestamp, $default_timezone);
        return $local_date->setTimezone($utc_timezone);
    }

    /**
     * @param \DateTime $last_edited
     */
    public function setLastEdited($last_edited)
    {
        $this->last_edited = $last_edited;
    }

    public function __construct()
    {
        $now = new \DateTime('now', new \DateTimeZone(self::DefaultTimeZone));
        $this->created = $now;
        $this->last_edited = $now;
    }

    /**
     * @return QueryBuilder
     */
    protected function createQueryBuilder():QueryBuilder
    {
        return Registry::getManager(self::EntityManager)->createQueryBuilder();
    }

    /**
     * @param string $dql
     * @return Query
     */
    protected function createQuery($dql):Query
    {
        return Registry::getManager(self::EntityManager)->createQuery($dql);
    }

    /**
     * @param $sql
     * @param array $params
     * @return Statement
     */
    protected function prepareRawSQL($sql, array $params = []):Statement
    {
        $stmt = Registry::getManager(self::EntityManager)->getConnection()->prepare($sql);
        if(count($params) > 0)
            $stmt = DoctrineStatementValueBinder::bind($stmt, $params);
        return $stmt;
    }

    /**
     * @param $sql
     * @param array $params
     * @return Statement
     */
    protected static function prepareRawSQLStatic($sql, array $params = []):Statement
    {
        $stmt = Registry::getManager(self::EntityManager)->getConnection()->prepare($sql);
        if(count($params) > 0)
            $stmt = DoctrineStatementValueBinder::bind($stmt, $params);
        return $stmt;
    }

    /**
     * @return EntityManager
     */
    protected function getEM():EntityManager
    {
        return Registry::getManager(self::EntityManager);
    }

    /**
     * @return EntityManager
     */
    protected static function getEMStatic():EntityManager
    {
        return Registry::getManager(self::EntityManager);
    }

    const EntityManager = 'model';

    public function updateLastEdited(): void
    {
        $now = new \DateTime('now', new \DateTimeZone(self::DefaultTimeZone));
        $this->last_edited = $now;
    }

    /**
     * @ORM\PreUpdate:
     */
    public function updating(PreUpdateEventArgs $args)
    {
        $this->updateLastEdited();
    }

}
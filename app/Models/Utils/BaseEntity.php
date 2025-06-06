<?php namespace App\Models\Utils;
/**
 * Copyright 2017 OpenStack Foundation
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

use Doctrine\ORM\Event\PreUpdateEventArgs;
use models\utils\IEntity;
use Doctrine\ORM\Mapping AS ORM;
use ReflectionClass;

/***
 * Class BaseEntity
 * @package App\Models\Utils
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
class BaseEntity implements IEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID', type: 'integer', unique: true, nullable: false)]
    protected $id;

    /**
     * @return bool
     */
    public function isNew():bool{
        return is_null($this->id);
    }

    /**
     * @return int
     */
    public function getIdentifier()
    {
        return (int)$this->id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getIdentifier();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $class_name = (new ReflectionClass($this))->getShortName();
        return "{$class_name}@{$this->getIdentifier()}";
    }

    #[ORM\PreUpdate] // :
    public function updating(PreUpdateEventArgs $args)
    {
    }
}
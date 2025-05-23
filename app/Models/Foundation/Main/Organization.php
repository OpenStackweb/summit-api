<?php namespace models\main;
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
use Doctrine\ORM\Mapping as ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package models\main
 */
#[ORM\Table(name: 'Org')]
#[ORM\Entity(repositoryClass: \repositories\main\DoctrineOrganizationRepository::class)]
class Organization extends SilverstripeBaseModel
{
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;
}
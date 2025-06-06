<?php namespace models\summit;
/**
 * Copyright 2020 OpenStack Foundation
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitMediaFileType')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitMediaFileTypeRepository::class)]
class SummitMediaFileType extends SilverstripeBaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->is_system_defined = false;
        $this->description = '';
    }

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string')]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'AllowedExtensions', type: 'string')]
    private $allowed_extensions;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsSystemDefine', type: 'boolean')]
    private $is_system_defined;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getAllowedExtensions(): ?string
    {
        return $this->allowed_extensions;
    }

    /**
     * @param string $allowed_extensions
     */
    public function setAllowedExtensions(string $allowed_extensions): void
    {
        $this->allowed_extensions = strtoupper($allowed_extensions);
    }

    /**
     * @return bool
     */
    public function IsSystemDefined(): bool
    {
        return $this->is_system_defined;
    }

    public function markAsSystemDefined(): void {
        $this->is_system_defined = true;
    }

    public function markAsUserDefined(): void {
        $this->is_system_defined = false;
    }
}
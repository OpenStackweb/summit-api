<?php namespace App\Models\Foundation\Software;
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package App\Models\Foundation\Software
 */
#[ORM\Table(name: 'OpenStackComponent')]
#[ORM\Entity]
class OpenStackComponent extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'CodeName', type: 'string')]
    private $code_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string')]
    private $description;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'IsCoreService', type: 'boolean')]
    private $is_core_service;

    /**
     * @var int
     */
    #[ORM\Column(name: '`Order`', type: 'integer')]
    private $order;

    public function __construct()
    {
        parent::__construct();
        $this->is_core_service = false;
        $this->order = 0;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getCodeName(): ?string
    {
        return $this->code_name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isIsCoreService(): bool
    {
        return $this->is_core_service;
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }


}
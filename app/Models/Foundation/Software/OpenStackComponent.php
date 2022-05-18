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
 * @ORM\Entity
 * @ORM\Table(name="OpenStackComponent")
 * Class OpenStackComponent
 * @package App\Models\Foundation\Software
 */
class OpenStackComponent extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="CodeName", type="string")
     * @var string
     */
    private $code_name;

    /**
     * @ORM\Column(name="Description", type="string")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="IsCoreService", type="boolean")
     * @var boolean
     */
    private $is_core_service;

    /**
     * @ORM\Column(name="`CustomOrder`", type="integer")
     * @var int
     */
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
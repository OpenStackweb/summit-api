<?php namespace App\Models\Foundation\Summit\Registration;
/*
 * Copyright 2023 OpenStack Foundation
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

use models\summit\SummitOwned;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping as ORM;

/**
 * @package App\Models\Foundation\Summit\Registration
 */
#[ORM\Table(name: 'SummitRegistrationFeedMetadata')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitRegistrationFeedMetadataRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'registration_feed_metadata')])]
class SummitRegistrationFeedMetadata extends SilverstripeBaseModel
{
    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
    use SummitOwned;

    /**
     * @var string
     */
    #[ORM\Column(name: '`Key`', type: 'string')]
    private $key;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Value', type: 'string')]
    private $value;

    /**
     * @param string $key
     * @param string $value
     */
    public function __construct(string $key, string $value)
    {
        parent::__construct();
        $this->key = trim($key);
        $this->value = trim($value);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }


}
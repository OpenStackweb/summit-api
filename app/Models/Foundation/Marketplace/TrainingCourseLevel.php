<?php namespace App\Models\Foundation\Marketplace;
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity
 * @ORM\Table(name="TrainingCourseType")
 * Class Distribution
 * @package App\Models\Foundation\Marketplace
 */
class TrainingCourseLevel extends SilverstripeBaseModel
{
    const ClassName = 'TrainingCourseLevel';

    /**
     * @return string
     */
    public function getClassName():string
    {
        return self::ClassName;
    }

    /**
     * @ORM\Column(name="Level", type="string")
     * @var string
     */
    protected $level;

    public function getLevel():?string{
        return $this->level;
    }


    /**
     * @ORM\Column(name="SortOrder", type="integer")
     * @var int
     */
    protected $order;

    public function getOrder():?int{
        return $this->order;
    }
}
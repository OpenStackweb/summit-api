<?php namespace models\summit;
/**
 * Copyright 2016 OpenStack Foundation
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

/**
 * Class SummitOwned
 * @package models\summit
 */
trait SummitOwned
{
    /**
     * @var Summit
     */
    #[ORM\JoinColumn(name: 'SummitID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\Summit::class)]
    protected $summit;

    /**
     * @param Summit $summit
     */
    public function setSummit(Summit $summit){
        $this->summit = $summit;
    }

    /**
     * @return Summit|null
     */
    public function getSummit():?Summit{
        return $this->summit;
    }

    /**
     * @return int
     */
    public function getSummitId():int{
        try {
            return is_null($this->summit) ? 0 : $this->summit->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasSummit():bool{
        return $this->getSummitId() > 0;
    }

    /**
     * @var int
     */
    public $former_summit_id = 0;

    public function clearSummit():void{
        if ($this->former_summit_id == 0) {
            $this->former_summit_id = is_null($this->summit) ? 0 : $this->summit->getId();
        }
        $this->summit = null;
    }
}
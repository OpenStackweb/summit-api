<?php namespace models\summit\CalendarSync\WorkQueue;
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
use models\summit\SummitAbstractLocation;
/**
 * Class AdminSummitLocationActionSyncWorkRequest
 * @package models\summit\CalendarSync\WorkQueue
 */
#[ORM\Table(name: 'AdminSummitLocationActionSyncWorkRequest')]
#[ORM\Entity]
class AdminSummitLocationActionSyncWorkRequest
extends AdminScheduleSummitActionSyncWorkRequest
{
    const SubType = 'ADMIN_LOCATION';

    /**
     * @var int
     */
    #[ORM\Column(name: 'LocationID', type: 'integer')]
    private $location_id;

    /**
     * @return SummitAbstractLocation
     */
    public function getLocation()
    {
        $id = $this->location_id;
        try {
            $location = $this->getEM()->find(SummitAbstractLocation::class, $id);
        }
        catch(\Exception $ex){
            return null;
        }
        return $location;
    }

    /**
     * @return int
     */
    public function getLocationId()
    {
        return $this->location_id;
    }

    /**
     * @param int $location_id
     */
    public function setLocationId($location_id)
    {
        $this->location_id = $location_id;
    }
}
<?php namespace models\summit;
/**
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

use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="SummitAttendeeBadgePrintBackUp")
 * Class SummitAttendeeBadgePrintBackUp
 * @package models\summit
 */
class SummitAttendeeBadgePrintBackUp extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="PrintDate", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $print_date;

    /**
     * @ORM\Column(name="BadgeID", type="integer")
     * @var int
     */
    private $badge_id;

    /**
     * @ORM\Column(name="RequestorID", type="integer")
     * @var int
     */
    private $requestor_id;

    /**
     * @ORM\Column(name="SummitBadgeViewTypeID", type="integer")
     * @var int|null
     */
    private $view_type_id;

    /**
     * @param \DateTime $print_date
     * @param int $badge_id
     * @param int $requestor_id
     * @param int|null $view_type_id
     */
    public function __construct(\DateTime $print_date, int $badge_id, int $requestor_id, ?int $view_type_id)
    {
        $this->print_date = $print_date;
        $this->badge_id = $badge_id;
        $this->requestor_id = $requestor_id;
        $this->view_type_id = $view_type_id;
    }
}
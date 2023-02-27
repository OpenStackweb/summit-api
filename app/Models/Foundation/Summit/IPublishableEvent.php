<?php namespace App\Models\Foundation\Summit;
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

use DateTime;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitAbstractLocation;
use models\summit\SummitEventType;
use models\utils\IEntity;

/**
 * Interface
 * @package App\Models\Foundation\Summit
 */
interface IPublishableEvent extends IEntity
{
    /**
     * @return SummitEventType|null
     */
    public function getType(): ?SummitEventType;

    public function hasType(): bool;

    /**
     * @return Summit|null
     */
    public function getSummit(): ?Summit;

    /**
     * @param DateTime $value
     */
    public function setStartDate(DateTime $value);

    /**
     * @param DateTime $value
     */
    public function setEndDate(DateTime $value);

    /**
     * @param int $duration_in_seconds
     * @param bool $skipDatesSetting
     * @throws ValidationException
     * @throws \Exception
     */
    public function setDuration(int $duration_in_seconds, bool $skipDatesSetting = false): void;

    /**
     * @return SummitAbstractLocation
     */
    public function getLocation(): ?SummitAbstractLocation;

    /**
     * @param SummitAbstractLocation $location
     */
    public function setLocation(SummitAbstractLocation $location);

    public function clearLocation();

    public function getTitle(): string;

    public function getLocationName(): string;
}
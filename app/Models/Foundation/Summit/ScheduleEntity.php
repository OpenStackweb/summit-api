<?php namespace App\Models\Foundation\Summit;
/*
 * Copyright 2022 OpenStack Foundation
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

use App\Events\ScheduleEntityLifeCycleEvent;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Illuminate\Support\Facades\Event;
use ReflectionClass;

/**
 * Trait ScheduleEntity
 * @package App\Models\Foundation\Summit
 */
trait ScheduleEntity
{
    /**
     * @return string
     */
    private function _getClassName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }

    /**
     * @return int
     */
    private function _getSummitId(): int
    {
        $rc = new ReflectionClass($this);
        if (!$rc->hasProperty("summit")) return 0;
        return $this->summit->id;
    }

    /**
     * @ORM\PreRemove:
     */
    public function deleting($args)
    {
        Event::dispatch(new ScheduleEntityLifeCycleEvent('DELETE',
            $this->_getSummitId(),
            $this->id,
            $this->_getClassName()));
    }

    /**
     * @ORM\preRemove:
     */
    public function deleted($args)
    {
    }

    /**
     * @ORM\PreUpdate:
     */
    public function updating(PreUpdateEventArgs $args)
    {
        parent::updating($args);
        Event::dispatch(new ScheduleEntityLifeCycleEvent('UPDATE',
            $this->_getSummitId(),
            $this->id,
            $this->_getClassName()));
    }

    /**
     * @ORM\PostUpdate:
     */
    public function updated($args)
    {
    }

    // events

    /**
     * @ORM\PostPersist
     */
    public function inserted($args)
    {
        Event::dispatch(new ScheduleEntityLifeCycleEvent('INSERT',
            $this->_getSummitId(),
            $this->id,
            $this->_getClassName()));
    }
}
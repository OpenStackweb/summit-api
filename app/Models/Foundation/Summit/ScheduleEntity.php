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
use App\Models\Utils\Traits\CachedEntity;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

/**
 * Trait ScheduleEntity
 * @package App\Models\Foundation\Summit
 */
trait ScheduleEntity
{
    use CachedEntity {
        deleted as protected cachedDeleted;
        updated as protected cachedUpdated;
    }

    protected bool $skip_data_update = false;


    public function skipDateUpdate():void{
        $this->skip_data_update = true;
    }

    public function shouldSkipDataUpdate():bool{
        return $this->skip_data_update;
    }

    /**
     * @return int
     */
    private function _getSummitId(): int
    {
        try {
            $rc = new ReflectionClass($this);

            if (!$rc->hasProperty("summit")){
                if ($rc->hasMethod("getSummitId"))
                    return $this->getSummitId();
                return 0;
            }
            if (is_null($this->summit)){
                if($rc->hasProperty("former_summit_id"))
                    return $this->former_summit_id;
                return 0;
            }
            return $this->summit->id;
        }
        catch(\Exception $ex){
        }
        return 0;
    }

    #[ORM\PreRemove]
    public function deleting($args)
    {
        try {
            Event::dispatch(new ScheduleEntityLifeCycleEvent(ScheduleEntityLifeCycleEvent::Operation_Delete,
                $this->_getSummitId(),
                $this->id,
                $this->_getClassName()));
        } catch (\Exception $ex) {
            // Lifecycle notifications must not abort Doctrine transactions.
            // A queue/cache failure here should never roll back the delete.
            Log::warning(sprintf(
                "ScheduleEntity::deleting failed to dispatch lifecycle event for %s id %s: %s",
                $this->_getClassName(), $this->id, $ex->getMessage()
            ));
        }
    }

    #[ORM\PostRemove]
    public function deleted($args)
    {
        try {
            $this->cachedDeleted($args);
        } catch (\Exception $ex) {
            Log::warning(sprintf(
                "ScheduleEntity::deleted failed cache cleanup for %s id %s: %s",
                $this->_getClassName(), $this->id, $ex->getMessage()
            ));
        }
    }

    #[ORM\PreUpdate]
    public function updating(PreUpdateEventArgs $args)
    {
        parent::updating($args);
     }

    #[ORM\PostUpdate]
    public function updated($args)
    {
        if($this->shouldSkipDataUpdate()){
            Log::debug(sprintf("ScheduleEntity::updated skipping data update for id %s type %s ...", $this->id, $this->_getClassName()));
            return;
        }
        Log::debug(sprintf("ScheduleEntity::updated id %s", $this->id));
        try {
            $this->cachedUpdated($args);
        } catch (\Exception $ex) {
            Log::warning(sprintf(
                "ScheduleEntity::updated failed cache update for %s id %s: %s",
                $this->_getClassName(), $this->id, $ex->getMessage()
            ));
        }
        try {
            Event::dispatch(new ScheduleEntityLifeCycleEvent(ScheduleEntityLifeCycleEvent::Operation_Update,
                $this->_getSummitId(),
                $this->id,
                $this->_getClassName()));
        } catch (\Exception $ex) {
            Log::warning(sprintf(
                "ScheduleEntity::updated failed to dispatch lifecycle event for %s id %s: %s",
                $this->_getClassName(), $this->id, $ex->getMessage()
            ));
        }
    }

    // events
    #[ORM\PostPersist]
    public function inserted($args)
    {
        try {
            Event::dispatch(new ScheduleEntityLifeCycleEvent(ScheduleEntityLifeCycleEvent::Operation_Insert,
                $this->_getSummitId(),
                $this->id,
                $this->_getClassName()));
        } catch (\Exception $ex) {
            Log::warning(sprintf(
                "ScheduleEntity::inserted failed to dispatch lifecycle event for %s id %s: %s",
                $this->_getClassName(), $this->id, $ex->getMessage()
            ));
        }
    }
}

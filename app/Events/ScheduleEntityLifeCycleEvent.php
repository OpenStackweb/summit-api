<?php namespace App\Events;
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

use Illuminate\Queue\SerializesModels;

/**
 * Class ScheduleEntityLifeCycleEvent
 * @package App\Events
 */
final class ScheduleEntityLifeCycleEvent extends Event
{
    use SerializesModels;

    /**
     * @var string
     */
    public $entity_operator;

    /**
     * @var int
     */
    public $summit_id;

    /**
     * @var int
     */
    public $entity_id;

    /**
     * @var string
     */
    public $entity_type;

    /**
     * @param string $entity_operator
     * @param int $summit_id
     * @param int $entity_id
     * @param string $entity_type
     */
    public function __construct(string $entity_operator, int $summit_id, int $entity_id, string $entity_type)
    {
        $this->entity_operator = $entity_operator;
        $this->summit_id = $summit_id;
        $this->entity_id = $entity_id;
        $this->entity_type = $entity_type;
    }

    public function __toString():string{
        return sprintf
        (
            "%s %s %s %s",
            $this->entity_operator,
            $this->summit_id,
            $this->entity_id,
            $this->entity_type
        );
    }

}
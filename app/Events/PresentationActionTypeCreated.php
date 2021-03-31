<?php namespace App\Events;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\summit\PresentationActionType;
/**
 * Class PresentationActionTypeCreated
 * @package App\Events
 */
final class PresentationActionTypeCreated extends Event
{
    use SerializesModels;

    /**
     * @var PresentationActionType
     */
    private $action_type;

    /**
     * PresentationMaterialCreated constructor.
     * @param PresentationActionType $action_type
     */
    public function __construct(PresentationActionType $action_type)
    {
        $this->action_type = $action_type;
    }

    public function getActionType():PresentationActionType{
        return $this->action_type;
    }
}
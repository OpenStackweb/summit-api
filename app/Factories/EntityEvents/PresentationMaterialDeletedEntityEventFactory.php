<?php namespace App\Factories\EntityEvents;
/**
 * Copyright 2018 OpenStack Foundation
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
use App\Events\PresentationMaterialDeleted;
use Illuminate\Support\Facades\App;
use models\summit\SummitEntityEvent;
/**
 * Class PresentationMaterialDeletedEntityEventFactory
 * @package App\Factories\EntityEvents
 */
final class PresentationMaterialDeletedEntityEventFactory
{
    /**
     * @param PresentationMaterialDeleted $event
     * @return SummitEntityEvent
     */
    public static function build(PresentationMaterialDeleted $event){

        $resource_server_context = App::make(\models\oauth2\IResourceServerContext::class);
        $owner                   = $resource_server_context->getCurrentUser(false);

        $entity_event = new SummitEntityEvent();
        $entity_event->setEntityClassName($event->getClassName());
        $entity_event->setEntityId($event->getMaterialId());
        $entity_event->setType('DELETE');

        if (!is_null($owner)) {
            $entity_event->setOwner($owner);
        }

        $entity_event->setSummit($event->getPresentation()->getSummit());

        return $entity_event;
    }
}
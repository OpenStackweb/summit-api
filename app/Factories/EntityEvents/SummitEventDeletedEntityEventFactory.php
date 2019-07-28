<?php
namespace App\Factories\EntityEvents;
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
use App\Events\SummitEventDeleted;
use models\summit\SummitEntityEvent;
use Illuminate\Support\Facades\App;
/**
 * Class SummitEventDeletedEntityEventFactory
 * @package App\Factories\EntityEvents
 */
final class SummitEventDeletedEntityEventFactory
{
    /**
     * @param SummitEventDeleted $event
     * @return SummitEntityEvent
     */
    public static function build(SummitEventDeleted $event){
        $args = $event->getArgs();

        $resource_server_context = App::make(\models\oauth2\IResourceServerContext::class);
        $owner                   = $resource_server_context->getCurrentUser();
        $params = $args->getParams();

        $entity_event = new SummitEntityEvent();
        $entity_event->setEntityClassName($params['class_name']);
        $entity_event->setEntityId($params['id']);
        $entity_event->setType('DELETE');

        if (!is_null($owner)) {
            $entity_event->setOwner($owner);
        }

        $entity_event->setSummit($params['summit']);
        $entity_event->setMetadata('');

        if(isset($params['published']) && $params['published']){
            // just record the published state at the moment of the update

            $entity_event->setMetadata( json_encode([
                'pub_old' => intval($params['published']),
                'pub_new' => intval($params['published'])
            ]));
        }
        return $entity_event;
    }
}
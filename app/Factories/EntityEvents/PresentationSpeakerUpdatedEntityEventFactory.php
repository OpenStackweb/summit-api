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
use App\Events\PresentationSpeakerUpdated;
use Illuminate\Support\Facades\App;
use models\summit\SummitEntityEvent;
/**
 * Class PresentationSpeakerUpdatedEntityEventFactory
 * @package App\Factories\EntityEvents
 */
final class PresentationSpeakerUpdatedEntityEventFactory
{
    /**
     * @param PresentationSpeakerUpdated $event
     * @return SummitEntityEvent[]
     */
    public static function build(PresentationSpeakerUpdated $event){
        $list                    = [];
        $resource_server_context = App::make(\models\oauth2\IResourceServerContext::class);
        $owner                   = $resource_server_context->getCurrentUser(false);

        foreach($event->getPresentationSpeaker()->getRelatedSummits() as $summit) {

            $entity_event = new SummitEntityEvent;
            $entity_event->setEntityClassName("PresentationSpeaker");
            $entity_event->setEntityId($event->getPresentationSpeaker()->getId());
            $entity_event->setType('UPDATE');

            if (!is_null($owner)) {
                $entity_event->setOwner($owner);
            }

            $entity_event->setSummit($summit);
            $entity_event->setMetadata('');

            $list[]  =$entity_event;
        }

        return $list;
    }
}
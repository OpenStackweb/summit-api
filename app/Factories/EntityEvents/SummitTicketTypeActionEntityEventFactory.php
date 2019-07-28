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
use App\Events\SummitTicketTypeAction;
use Illuminate\Support\Facades\App;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\SummitEntityEvent;
/**
 * Class SummitTicketTypeActionEntityEventFactory
 * @package App\Factories\EntityEvents
 */
final class SummitTicketTypeActionEntityEventFactory
{
    /**
     * @param SummitTicketTypeAction $event
     * @param string $type
     * @return SummitEntityEvent
     */
    public static function build(SummitTicketTypeAction $event, $type = 'UPDATE')
    {
        $resource_server_context = App::make(IResourceServerContext::class);
        $summit_repository       = App::make(ISummitRepository::class);
        $summit                  = $summit_repository->getById($event->getSummitId());
        $owner                   = $resource_server_context->getCurrentUser();

        $entity_event = new SummitEntityEvent;
        $entity_event->setEntityClassName('SummitTicketType');
        $entity_event->setEntityId($event->getTicketTypeId());
        $entity_event->setType($type);

        if (!is_null($owner)) {
            $entity_event->setOwner($owner);
        }

        $entity_event->setSummit($summit);
        $entity_event->setMetadata('');

        return $entity_event;
    }
}
<?php namespace App\Factories\CalendarAdminActionSyncWorkRequest;
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
use App\Events\SummitEventUpdated;
use models\summit\CalendarSync\WorkQueue\AbstractCalendarSyncWorkRequest;
use models\summit\CalendarSync\WorkQueue\AdminSummitEventActionSyncWorkRequest;
use Illuminate\Support\Facades\App;
/**
 * Class SummitEventUpdatedCalendarSyncWorkRequestFactory
 * @package App\Factories\CalendarAdminActionSyncWorkRequest
 */
final class SummitEventUpdatedCalendarSyncWorkRequestFactory
{
    /**
     * @param SummitEventUpdated $event
     * @return AdminSummitEventActionSyncWorkRequest
     */
    public static function build(SummitEventUpdated $event){
        $resource_server_context         = App::make(\models\oauth2\IResourceServerContext::class);
        $args                            = $event->getArgs();
        $current_member                  = $resource_server_context->getCurrentUser(false);
        // sync request from admin
        $request = new AdminSummitEventActionSyncWorkRequest();
        $request->setSummitEvent($event->getSummitEvent()) ;
        $request->setType(AbstractCalendarSyncWorkRequest::TypeUpdate);
        if(!is_null($current_member)){
            $request->setCreatedBy($current_member);
        }

        if($args->hasChangedField('published')){
            $pub_old = intval($args->getOldValue('published'));
            $pub_new = intval($args->getNewValue('published'));
            if($pub_old == 1 && $pub_new == 0)
                $request->setType(AbstractCalendarSyncWorkRequest::TypeRemove);
        }

        return $request;
    }
}
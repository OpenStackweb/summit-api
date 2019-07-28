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
use App\Events\LocationAction;
use App\Models\Foundation\Summit\Repositories\ISummitLocationRepository;
use Illuminate\Support\Facades\App;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\CalendarSync\WorkQueue\AdminSummitLocationActionSyncWorkRequest;
/**
 * Class AdminSummitLocationActionSyncWorkRequestFactory
 * @package App\Factories\CalendarAdminActionSyncWorkRequest
 */
final class AdminSummitLocationActionSyncWorkRequestFactory
{
    /**
     * @param LocationAction $event
     * @param string $type
     * @return AdminSummitLocationActionSyncWorkRequest
     */
    public static function build(LocationAction $event, $type){

        $resource_server_context = App::make(IResourceServerContext::class);
        $current_member          = $resource_server_context->getCurrentUser();
        $request                 = new AdminSummitLocationActionSyncWorkRequest;

        $request->setLocationId($event->getLocationId());

        $request->setType($type);

        if(!is_null($current_member)){
            $request->setCreatedBy($current_member);
        }

        return $request;
    }
}
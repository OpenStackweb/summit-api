<?php namespace App\Services\Apis\ExternalScheduleFeeds;
/**
 * Copyright 2019 OpenStack Foundation
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
use models\summit\Summit;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\App;
use App\Models\Foundation\Summit\ISummitExternalScheduleFeedType;
/**
 * Class ExternalScheduleFeedFactory
 * @package App\Services\Apis\ExternalScheduleFeeds
 */
final class ExternalScheduleFeedFactory implements IExternalScheduleFeedFactory
{

    /**
     * @param Summit $summit
     * @return IExternalScheduleFeed
     */
    public function build(Summit $summit): ?IExternalScheduleFeed
    {
       $client = App::make(ClientInterface::class);
       switch ($summit->getApiFeedType()){
           case ISummitExternalScheduleFeedType::SchedType:
               return new SchedScheduleFeed($summit, $client);
           case ISummitExternalScheduleFeedType::VanderpoelType:
               return new VanderpoelScheduleFeed($summit, $client);
       }
       return null;
    }
}
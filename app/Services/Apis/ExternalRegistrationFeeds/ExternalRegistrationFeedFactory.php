<?php namespace App\Services\Apis\ExternalRegistrationFeeds;
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
use App\Models\Foundation\Summit\Registration\ISummitExternalRegistrationFeedType;
use App\Services\Apis\ExternalRegistrationFeeds\implementations\EventbriteRegistrationFeed;
use models\summit\Summit;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\App;
/**
 * Class ExternalRegistrationFeedFactory
 * @package App\Services\Apis\ExternalRegistrationFeeds
 */
final class ExternalRegistrationFeedFactory implements IExternalRegistrationFeedFactory
{

    /**
     * @param Summit $summit
     * @return IExternalRegistrationFeed|null
     */
    public function build(Summit $summit): ?IExternalRegistrationFeed
    {
        $client = App::make(ClientInterface::class);
        switch ($summit->getExternalRegistrationFeedType()){
            case ISummitExternalRegistrationFeedType::Eventbrite:
                return new EventbriteRegistrationFeed($summit, $client);
                break;
        }
        return null;
    }
}
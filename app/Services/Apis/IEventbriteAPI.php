<?php namespace services\apis;
/**
 * Copyright 2016 OpenStack Foundation
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
use DateTime;
use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeedResponse;
use models\summit\Summit;
/**
 * Interface IEventbriteAPI
 * @package services\apis
 */
interface IEventbriteAPI
{
    const QuestionChoicesCharSeparator = '|';
    /**
     * @param array $auth_info
     * @return $this
     */
    public function setCredentials(array $auth_info);

     /**
     * @param string $order_id
     * @return mixed
     */
    public function getOrder($order_id);

    /**
     * @param Summit $summit
     * @param int $page
     * @param string $expand
     * @return mixed
     */
    public function getTicketTypes(Summit $summit, int $page = 1, string $expand = 'ticket_classes'):IExternalRegistrationFeedResponse;

    /**
     * @param Summit $summit
     * @param int $page
     * @param string $expand
     * @return mixed
     */
    public function getExtraQuestions(Summit $summit,int $page = 1, string $expand = null):IExternalRegistrationFeedResponse;

    /**
     * @param Summit $summit
     * @param int $page
     * @param DateTime|null $changed_since
     * @param string $expand
     * @return mixed
     */
    public function getAttendees(Summit $summit, int $page = 1, ?DateTime $changed_since = null, string $expand = 'promotional_code');
}
<?php namespace App\Http\Controllers;
/**
 * Copyright 2015 OpenStack Foundation
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

use libs\utils\PaginationValidationRules;
use models\exceptions\ValidationException;
use models\summit\Presentation;
use models\summit\SummitEvent;
use utils\Filter;
use utils\Order;
use utils\OrderParser;
use utils\PagingResponse;
use Illuminate\Support\Facades\Validator;
use utils\FilterParser;
use utils\PagingInfo;
use Illuminate\Support\Facades\Request;

/**
 * Class RetrieveSummitEventsStrategy
 * @package App\Http\Controllers
 */
abstract class RetrieveSummitEventsStrategy
{

    protected function getPageParams()
    {
        // default values
        $page = 1;
        $per_page = PaginationValidationRules::PerPageMin;

        if (Request::has('page')) {
            $page = intval(Request::input('page'));
            $per_page = intval(Request::input('per_page'));
        }

        return [$page, $per_page];
    }

    /**
     * @param array $params
     * @return PagingResponse
     * @throws ValidationException
     */
    public function getEvents(array $params = [])
    {
        $values = Request::all();

        $rules = PaginationValidationRules::get();
        $validation = Validator::make($values, $rules);

        if ($validation->fails()) {
            $ex = new ValidationException();
            throw $ex->setMessages($validation->messages()->toArray());
        }


        list($page, $per_page) = $this->getPageParams();

        return $this->retrieveEventsFromSource
        (
            new PagingInfo($page, $per_page), $this->buildFilter(), $this->buildOrder()
        );
    }

    /**
     * @return null|Filter
     */
    public function getFilter()
    {
        return $this->buildFilter();
    }

    /**
     * @return null|Filter
     */
    protected function buildFilter()
    {

        $filter = null;

        if (Request::has('filter')) {
            $filter = FilterParser::parse(Request::input('filter'), $this->getValidFilters());
        }

        if (is_null($filter)) $filter = new Filter();

        $filter_validator_rules = $this->getFilterValidatorRules();
        if (count($filter_validator_rules)) {
            $filter->validate($filter_validator_rules);
        }

        return $filter;
    }

    /**
     * @return null|Order
     */
    protected function buildOrder()
    {
        $order = null;
        if (Request::has('order')) {
            $order = OrderParser::parse(Request::input('order'), [
                'title',
                'start_date',
                'end_date',
                'id',
                'created',
                'track',
                'random',
                'page_random',
                'custom_order',
                'votes_count',
                'duration',
                'speakers_count',
                'created_by_fullname',
                'created_by_email',
                'sponsor',
                'created_by_company',
                'speaker_company',
                'level',
                'etherpad_link',
                'streaming_url',
                'streaming_type',
                'meeting_url',
                'location',
                'tags',
                'event_type',
                'event_type_capacity',
                'is_published',
                'speakers',
                'selection_status',
                'published_date',
                'selection_plan',
            ]);
        }
        return $order;
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    abstract public function retrieveEventsFromSource(PagingInfo $paging_info, Filter $filter = null, Order $order = null);

    /**
     * @return array
     */
    protected function getValidFilters()
    {
        return [

            'title' => ['=@', '@@', '=='],
            'abstract' => ['=@', '@@', '=='],
            'meeting_url' => ['=@', '@@', '=='],
            'streaming_url' => ['=@', '@@', '=='],
            'etherpad_link' => ['=@', '@@', '=='],
            'social_summary' => ['=@', '@@', '=='],
            'tags' => ['=@', '@@', '=='],
            'level' => ['=@', '@@', '=='],
            'start_date' => ['>', '<', '<=', '>=', '=='],
            'end_date' => ['>', '<', '<=', '>=', '=='],
            'summit_type_id' => ['=='],
            'event_type_id' => ['=='],
            'track_id' => ['=='],
            'track_group_id' => ['=='],
            'speaker_id' => ['=='],
            'sponsor_id' => ['=='],
            'summit_id' => ['=='],
            'sponsor' => ['=@', '@@', '=='],
            'location_id' => ['=='],
            'speaker' => ['=@', '@@', '=='],
            'speaker_email' => ['=@', '@@', '=='],
            'speaker_title' => ['=@', '@@', '=='],
            'speaker_company' => ['=@', '@@', '=='],
            'selection_status' => ['=='],
            'id' => ['=='],
            'selection_plan_id' => ['=='],
            'created_by_fullname' => ['=@', '@@', '=='],
            'created_by_email' => ['=@', '@@', '=='],
            'created_by_company' => ['=@', '@@', '=='],
            'type_allows_publishing_dates' => ['=='],
            'type_allows_location' => ['=='],
            'type_allows_attendee_vote' => ['=='],
            'type_allows_custom_ordering' => ['=='],
            'published' => ['=='],
            'class_name' => ['=='],
            'presentation_attendee_vote_date' => ['>', '<', '<=', '>=', '=='],
            'votes_count' => ['>', '<', '<=', '>=', '=='],
            'duration' => ['>', '<', '<=', '>=', '=='],
            'speakers_count' => ['>', '<', '<=', '>=', '=='],
            'streaming_type' => ['=='],
            'submission_status' => ['=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'title' => 'sometimes|string',
            'abstract' => 'sometimes|string',
            'social_summary' => 'sometimes|string',
            'tags' => 'sometimes|string',
            'level' => 'sometimes|string',
            'speaker' => 'sometimes|string',
            'speaker_email' => 'sometimes|string',
            'speaker_title' => 'sometimes|string',
            'speaker_company' => 'sometimes|string',
            'start_date' => 'sometimes|date_format:U',
            'end_date' => 'sometimes|date_format:U',
            'summit_type_id' => 'sometimes|integer',
            'event_type_id' => 'sometimes|integer',
            'track_id' => 'sometimes|integer',
            'track_group_id' => 'sometimes|integer',
            'summit_id' => 'sometimes|integer',
            'speaker_id' => 'sometimes|integer',
            'location_id' => 'sometimes|integer',
            'id' => 'sometimes|integer',
            'selection_plan_id' => 'sometimes|integer',
            'created_by_fullname' => 'sometimes|string',
            'created_by_email' => 'sometimes|string',
            'created_by_company' => 'sometimes|string',
            'type_allows_publishing_dates' => 'sometimes|boolean',
            'type_allows_location' => 'sometimes|boolean',
            'type_allows_attendee_vote' => 'sometimes|boolean',
            'type_allows_custom_ordering' => 'sometimes|boolean',
            'published' => 'sometimes|boolean',
            'class_name' => 'sometimes|string|in:' . implode(',', [Presentation::ClassName, SummitEvent::ClassName]),
            'presentation_attendee_vote_date' => 'sometimes|date_format:U',
            'votes_count' => 'sometimes|integer',
            'selection_status' => 'sometimes|string|in:selected,accepted,rejected,alternate,lightning-accepted,lightning-alternate',
            'duration' => 'sometimes|integer',
            'speakers_count' => 'sometimes|integer',
            'meeting_url' => 'sometimes|string',
            'streaming_url' => 'sometimes|string',
            'etherpad_link' => 'sometimes|string',
            'streaming_type' => 'sometimes|string|in:VOD,LIVE',
            'submission_status' => 'sometimes|string|in:Accepted,Received,NonReceived',
        ];
    }
}
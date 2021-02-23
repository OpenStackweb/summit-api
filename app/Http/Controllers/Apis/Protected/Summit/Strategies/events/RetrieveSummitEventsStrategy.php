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
use models\exceptions\ValidationException;
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

    protected function getPageParams(){
        // default values
        $page     = 1;
        $per_page = 5;

        if (Request::has('page')) {
            $page     = intval(Request::input('page'));
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

            $rules = [
                'page'     => 'integer|min:1',
                'per_page' => 'required_with:page|integer|min:5|max:100',
            ];

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
    protected function buildFilter(){

        $filter = null;

        if (Request::has('filter')) {
            $filter = FilterParser::parse(Request::input('filter'), $this->getValidFilters());
        }

        if(is_null($filter)) $filter = new Filter();

        $filter_validator_rules = $this->getFilterValidatorRules();
        if(count($filter_validator_rules)) {
            $filter->validate($filter_validator_rules);
        }

        return $filter;
    }

    /**
     * @return null|Order
     */
    protected function buildOrder(){
        $order = null;
        if (Request::has('order'))
        {
            $order = OrderParser::parse(Request::input('order'), [

                'title',
                'start_date',
                'end_date',
                'id',
                'created',
                'track',
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

            'title'            => ['=@', '=='],
            'abstract'         => ['=@', '=='],
            'social_summary'   => ['=@', '=='],
            'tags'             => ['=@', '=='],
            'level'            => ['=@', '=='],
            'start_date'       => ['>', '<', '<=', '>=', '=='],
            'end_date'         => ['>', '<', '<=', '>=', '=='],
            'summit_type_id'   => ['=='],
            'event_type_id'    => ['=='],
            'track_id'         => ['=='],
            'speaker_id'       => ['=='],
            'sponsor_id'       => ['=='],
            'sponsor'          => ['=@', '=='],
            'location_id'      => ['=='],
            'speaker'          => ['=@', '=='],
            'speaker_email'    => ['=@', '=='],
            'speaker_title'    => ['=@', '=='],
            'speaker_company'  => ['=@', '=='],
            'selection_status' => ['=='],
            'id'               => ['=='],
            'selection_plan_id' => ['=='],
            'created_by_fullname'  => ['=@', '=='],
            'created_by_email'  => ['=@', '=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'title'           => 'sometimes|string',
            'abstract'        => 'sometimes|string',
            'social_summary'  => 'sometimes|string',
            'tags'            => 'sometimes|string',
            'level'           => 'sometimes|string',
            'speaker'         => 'sometimes|string',
            'speaker_email'   => 'sometimes|string',
            'speaker_title'   => 'sometimes|string',
            'speaker_company' => 'sometimes|string',
            'start_date'      => 'sometimes|date_format:U',
            'end_date'        => 'sometimes|date_format:U',
            'summit_type_id'  => 'sometimes|integer',
            'event_type_id'   => 'sometimes|integer',
            'track_id'        => 'sometimes|integer',
            'speaker_id'      => 'sometimes|integer',
            'location_id'     => 'sometimes|integer',
            'id'              => 'sometimes|integer',
            'selection_plan_id' => 'sometimes|integer',
            'created_by_fullname'  => 'sometimes|string',
            'created_by_email'  => 'sometimes|string',
        ];
    }
}
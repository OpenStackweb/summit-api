<?php namespace App\Http\Controllers;
/**
 * Copyright 2017 OpenStack Foundation
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
use utils\Filter;
use utils\FilterParser;
use utils\Order;
use utils\OrderParser;
use Illuminate\Support\Facades\Request;
/**
 * Class RetrieveAllUnPublishedSummitEventsStrategy
 * @package App\Http\Controllers
 */
class RetrieveAllUnPublishedSummitEventsStrategy extends RetrieveAllSummitEventsBySummitStrategy
{
    /**
     * @return array
     */
    protected function getValidFilters()
    {
        $valid_filters = parent::getValidFilters();
        $valid_filters['published'] = ['=='];
        return $valid_filters;
    }

    /**
     * @return null|Filter
     */
    protected function buildFilter()
    {
        $filter = parent::buildFilter();
        $filter->addFilterCondition(FilterParser::buildFilter('published','==','0'));
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
                'trackchairsel'
            ]);
        }
        return $order;
    }
}
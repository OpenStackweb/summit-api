<?php namespace App\Http\Controllers;
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
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use utils\Filter;
use utils\FilterParser;
use utils\Order;
use utils\OrderParser;
use utils\PagingInfo;
use App\Http\Utils\PagingConstants;
use Exception;
/**
 * Trait ParametrizedGetAll
 * @package App\Http\Controllers
 */
trait ParametrizedGetAll
{
    use BaseAPI;

    /**
     * @param int $page
     * @param int $per_page
     * @param Filter $filter
     * @param Order $order
     * @param callable $applyExtraFilters
     * @return \utils\PagingResponse
     */
    protected function defaultQuery(int $page, int $per_page, Filter $filter, Order $order, callable $applyExtraFilters){
       return $this->getRepository()->getAllByPage
        (
            new PagingInfo($page, $per_page),
            call_user_func($applyExtraFilters, $filter),
            $order
        );
    }

    /**
     * @param callable $getFilterRules
     * @param callable $getFilterValidatorRules
     * @param callable $getOrderRules
     * @param callable $applyExtraFilters
     * @param callable $serializerType
     * @param callable|null $defaultOrderRules
     * @param callable|null $defaultPageSize
     * @return mixed
     */
    public function getAll
    (
        callable $getFilterRules,
        callable $getFilterValidatorRules,
        callable $getOrderRules,
        callable $applyExtraFilters,
        callable $serializerType,
        callable $defaultOrderRules = null,
        callable $defaultPageSize   = null,
        callable $queryCallable = null
    )
    {
        $values = Input::all();

        $rules  = [

            'page'     => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = is_null($defaultPageSize) ? PagingConstants::DefaultPageSize : call_user_func($defaultPageSize);

            if (Input::has('page')) {
                $page     = intval(Input::get('page'));
            }

            if (Input::has('per_page')) {
                $per_page = intval(Input::get('per_page'));
            }

            $filter = null;

            if (Input::has('filter')) {
                $filter = FilterParser::parse(Input::get('filter'), call_user_func($getFilterRules));
            }

            if(is_null($filter)) $filter = new Filter();

            $filter_validator_rules = call_user_func($getFilterValidatorRules);
            if(count($filter_validator_rules)) {
                $filter->validate($filter_validator_rules);
            }

            $order = null;

            if (Input::has('order'))
            {
                $order = OrderParser::parse(Input::get('order'), call_user_func($getOrderRules));
            }
            else{
                if(!is_null($defaultOrderRules)){
                    $order = call_user_func($defaultOrderRules);
                }
            }

            if(!is_null($queryCallable))
                $data = call_user_func($queryCallable,
                    $page,
                    $per_page,
                    $filter,
                    $order,
                    $applyExtraFilters);
            else
                $data =  $this->defaultQuery
                (
                    $page,
                    $per_page,
                    $filter,
                    $order,
                    $applyExtraFilters
                );

            $fields    = Request::input('fields', '');
            $relations = Request::input('relations', '');

            $relations = !empty($relations) ? explode(',', $relations) : [];
            $fields    = !empty($fields) ? explode(',', $fields) : [];

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    $fields,
                    $relations,
                    [],
                    call_user_func($serializerType)
                )
            );
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param callable $getFilterRules
     * @param callable $getFilterValidatorRules
     * @param callable $getOrderRules
     * @param callable $applyExtraFilters
     * @param callable $serializerType
     * @param callable $getFormatters
     * @param callable $getColumns
     * @param $file_prefix
     * @param array $serializer_params
     * @return mixed
     */
    public function getAllCSV
    (
        callable $getFilterRules,
        callable $getFilterValidatorRules,
        callable $getOrderRules,
        callable $applyExtraFilters,
        callable $serializerType,
        callable $getFormatters,
        callable $getColumns,
        $file_prefix,
        array $serializer_params = []
    )
    {
        $values = Input::all();
        $rules  = [
            'page'     => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PHP_INT_MAX;

            if (Input::has('page')) {
                $page     = intval(Input::get('page'));
                $per_page = intval(Input::get('per_page'));
            }

            if (Input::has('per_page')) {
                $per_page = intval(Input::get('per_page'));
            }

            $filter = null;

            if (Input::has('filter')) {
                $filter = FilterParser::parse(Input::get('filter'), call_user_func($getFilterRules));
            }

            if(is_null($filter)) $filter = new Filter();

            $filter_validator_rules = call_user_func($getFilterValidatorRules);
            if(count($filter_validator_rules)) {
                $filter->validate($filter_validator_rules);
            }

            $order = null;

            if (Input::has('order'))
            {
                $order = OrderParser::parse(Input::get('order'), call_user_func($getOrderRules));
            }

            $data = $this->getRepository()->getAllByPage
            (
                new PagingInfo($page, $per_page),
                call_user_func($applyExtraFilters, $filter),
                $order
            );

            $filename = $file_prefix . date('Ymd');
            $list     = $data->toArray
            (
                Request::input('expand', ''),
                [],
                [],
                $serializer_params,
                call_user_func($serializerType)
            );
            return $this->export
            (
                'csv',
                $filename,
                $list['data'],
                call_user_func($getFormatters),
                call_user_func($getColumns)
            );
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
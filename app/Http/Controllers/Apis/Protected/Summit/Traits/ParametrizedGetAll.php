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

use App\ModelSerializers\SerializerUtils;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use libs\utils\PaginationValidationRules;
use models\exceptions\ValidationException;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\Order;
use utils\OrderParser;
use utils\PagingInfo;

/**
 * Trait ParametrizedGetAll
 * @package App\Http\Controllers
 */
trait ParametrizedGetAll
{
    use BaseAPI;

    use RequestProcessor;

    use ParseAndGetFilter;

    use ParseAndGetPaginationParams;

    /**
     * @param int $page
     * @param int $per_page
     * @param Filter|null $filter
     * @param Order|null $order
     * @param callable|null $applyExtraFilters
     * @return \utils\PagingResponse
     */
    protected function defaultQuery(int $page, int $per_page, ?Filter $filter, ?Order $order, ?callable $applyExtraFilters = null)
    {
        if (!is_null($applyExtraFilters))
            $filter = call_user_func($applyExtraFilters, $filter);

        return $this->getRepository()->getAllByPage
        (
            new PagingInfo($page, $per_page),
            $filter,
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
     * @param callable|null $queryCallable
     * @param array $serializerParams
     * @return mixed
     */
    public function _getAll
    (
        callable $getFilterRules,
        callable $getFilterValidatorRules,
        callable $getOrderRules,
        callable $applyExtraFilters,
        callable $serializerType = null,
        callable $defaultOrderRules = null,
        callable $defaultPageSize = null,
        callable $queryCallable = null,
        array    $serializerParams = []
    )
    {
        return $this->processRequest(function () use (
            $getFilterRules,
            $getFilterValidatorRules,
            $getOrderRules,
            $applyExtraFilters,
            $serializerType,
            $defaultOrderRules,
            $defaultPageSize,
            $queryCallable,
            $serializerParams
        ) {
            $values = Request::all();

            $rules = PaginationValidationRules::get();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            list($page, $per_page) = self::getPaginationParams();

            $filter = self::getFilter($getFilterRules, $getFilterValidatorRules);

            $order = null;

            if (Request::has('order')) {
                $order = OrderParser::parse(Request::get('order'), call_user_func($getOrderRules));
            } else {
                if (!is_null($defaultOrderRules)) {
                    $order = call_user_func($defaultOrderRules);
                }
            }

            if (!is_null($queryCallable))
                $data = call_user_func($queryCallable,
                    $page,
                    $per_page,
                    $filter,
                    $order,
                    $applyExtraFilters);
            else
                $data = $this->defaultQuery
                (
                    $page,
                    $per_page,
                    $filter,
                    $order,
                    $applyExtraFilters
                );

            $serializerParams['filter'] = $filter;

            return $this->ok
            (
                $data->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    $serializerParams,
                    $serializerType  && is_callable($serializerType) ? call_user_func($serializerType) : SerializerRegistry::SerializerType_Public
                )
            );

        });
    }

    /**
     * @param callable $getFilterRules
     * @param callable $getFilterValidatorRules
     * @param callable $getOrderRules
     * @param callable $applyExtraFilters
     * @param callable $serializerType
     * @param callable $getFormatters
     * @param callable $getColumns
     * @param string $file_prefix
     * @param array $serializerParams
     * @param callable|null $queryCallable
     * @return mixed
     */
    public function _getAllCSV
    (
        callable $getFilterRules,
        callable $getFilterValidatorRules,
        callable $getOrderRules,
        callable $applyExtraFilters,
        callable $serializerType,
        callable $getFormatters,
        callable $getColumns,
        string   $file_prefix = 'file-',
        array    $serializerParams = [],
        callable $queryCallable = null,
        callable $preProcessSerializerParams = null,
    )
    {

        return $this->processRequest(function () use (
            $getFilterRules,
            $getFilterValidatorRules,
            $getOrderRules,
            $applyExtraFilters,
            $serializerType,
            $getFormatters,
            $getColumns,
            $file_prefix,
            $serializerParams,
            $queryCallable,
            $preProcessSerializerParams
        ) {

            $values = Request::all();
            $rules = PaginationValidationRules::get();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page = 1;
            $per_page = PHP_INT_MAX;

            if (Request::has('page')) {
                $page = intval(Request::get('page'));
                $per_page = intval(Request::get('per_page'));
            }

            if (Request::has('per_page')) {
                $per_page = intval(Request::get('per_page'));
            }

            $filter = self::getFilter($getFilterRules, $getFilterValidatorRules);

            $order = null;

            if (Request::has('order')) {
                $order = OrderParser::parse(Request::get('order'), call_user_func($getOrderRules));
            }

            if (!is_null($queryCallable))
                $data = call_user_func($queryCallable,
                    $page,
                    $per_page,
                    $filter,
                    $order,
                    $applyExtraFilters);
            else
                $data = $this->defaultQuery
                (
                    $page,
                    $per_page,
                    $filter,
                    $order,
                    $applyExtraFilters
                );

            $filename = $file_prefix . date('Ymd');

            $serializerParams['filter'] = $filter;

            if(!is_null($preProcessSerializerParams))
                $serializerParams = call_user_func($preProcessSerializerParams, $data, $serializerParams);

            $list = $data->toArray
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                $serializerParams,
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

        });
    }
}
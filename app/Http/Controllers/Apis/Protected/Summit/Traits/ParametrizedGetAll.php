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

use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Models\Exceptions\AuthzException;
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
     * @param Filter|null $filter
     * @param Order|null $order
     * @param callable|null $applyExtraFilters
     * @return \utils\PagingResponse
     */
    protected function defaultQuery(int $page, int $per_page, ?Filter $filter, ?Order $order, ?callable $applyExtraFilters = null)
    {
        if(!is_null($applyExtraFilters))
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
        callable $serializerType,
        callable $defaultOrderRules = null,
        callable $defaultPageSize = null,
        callable $queryCallable = null,
        array $serializerParams = []
    )
    {
        $values = Request::all();

        $rules = [

            'page' => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page = 1;
            $per_page = is_null($defaultPageSize) ? PagingConstants::DefaultPageSize : call_user_func($defaultPageSize);

            if (Request::has('page')) {
                $page = intval(Request::get('page'));
            }

            if (Request::has('per_page')) {
                $per_page = intval(Request::get('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::get('filter'), call_user_func($getFilterRules));
            }

            if (is_null($filter)) $filter = new Filter();

            $filter_validator_rules = call_user_func($getFilterValidatorRules);
            if (count($filter_validator_rules)) {
                $filter->validate($filter_validator_rules);
            }

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

            $fields = Request::input('fields', '');
            $relations = Request::input('relations', '');

            $relations = !empty($relations) ? explode(',', $relations) : [];
            $fields = !empty($fields) ? explode(',', $fields) : [];

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    $fields,
                    $relations,
                    $serializerParams,
                    call_user_func($serializerType)
                )
            );
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch(HTTP403ForbiddenException $ex){
            Log::warning($ex);
            return $this->error403();
        }
        catch(AuthzException $ex){
            Log::warning($ex);
            return $this->error403($ex->getMessage());
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
        string $file_prefix = 'file-',
        array $serializerParams = [],
        callable $queryCallable = null
    )
    {
        $values = Request::all();
        $rules = [
            'page' => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {

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

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::get('filter'), call_user_func($getFilterRules));
            }

            if (is_null($filter)) $filter = new Filter();

            $filter_validator_rules = call_user_func($getFilterValidatorRules);
            if (count($filter_validator_rules)) {
                $filter->validate($filter_validator_rules);
            }

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

            $fields = Request::input('fields', '');
            $relations = Request::input('relations', '');

            $relations = !empty($relations) ? explode(',', $relations) : [];
            $fields = !empty($fields) ? explode(',', $fields) : [];

            $list = $data->toArray
            (
                Request::input('expand', ''),
                $fields,
                $relations,
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
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        } catch(AuthzException $ex){
            Log::warning($ex);
            return $this->error403($ex->getMessage());
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
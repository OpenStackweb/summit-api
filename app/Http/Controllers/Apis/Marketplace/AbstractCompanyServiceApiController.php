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

use libs\utils\PaginationValidationRules;
use models\utils\IBaseRepository;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Illuminate\Support\Facades\Validator;
use utils\Filter;
use utils\FilterParser;
use utils\OrderParser;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use utils\PagingInfo;
/**
 * Class AbstractCompanyServiceApiController
 * @package App\Http\Controllers
 */
abstract class AbstractCompanyServiceApiController extends JsonController
{
    /**
     * @var IBaseRepository
     */
    protected $repository;

    /**
     * AppliancesApiController constructor.
     * @param IBaseRepository $repository
     */
    public function __construct(IBaseRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return mixed
     */
    public function getAll(){
        $values = Request::all();

        $rules = PaginationValidationRules::get();

        try {

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PaginationValidationRules::PerPageMin;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'),  array
                (
                    'name'    => ['=@', '=='],
                    'company' => ['=@', '=='],
                ));
            }

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), array
                (
                    'name',
                    'company',
                    'id',
                ));
            }

            if(is_null($filter)) $filter = new Filter();

            $data      = $this->repository->getAllByPage(new PagingInfo($page, $per_page), $filter, $order);
            $fields    = Request::input('fields', '');
            $fields    = !empty($fields) ? explode(',', $fields) : [];
            $relations = Request::input('relations', '');
            $relations = !empty($relations) ? explode(',', $relations) : [];
            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    $fields,
                    $relations
                )
            );
        }
        catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        }
        catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412($ex2->getMessages());
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
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
use models\main\IGroupRepository;
use models\oauth2\IResourceServerContext;
use utils\Filter;
use utils\FilterParser;
use utils\FilterParserException;
use utils\OrderParser;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use utils\PagingInfo;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Illuminate\Support\Facades\Validator;
/**
 * Class OAuth2GroupsApiController
 * @package App\Http\Controllers
 */
final class OAuth2GroupsApiController extends OAuth2ProtectedController
{
    /**
     * OAuth2MembersApiController constructor.
     * @param IGroupRepository $group_repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IGroupRepository $group_repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $group_repository;
    }

    public function getAll(){

        $values = Request::all();

        $rules = array
        (
            'page'     => 'integer|min:1',
            'per_page' => 'required_with:page|integer|min:5|max:100',
        );

        try {

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = 5;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'),  array
                (
                    'code' => ['=@', '=='],
                    'title' => ['=@', '=='],
                ));
            }

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), array
                (
                    'code',
                    'title',
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
        catch(FilterParserException $ex3){
            Log::warning($ex3);
            return $this->error412($ex3->getMessages());
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

}
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

use App\ModelSerializers\SerializerUtils;
use models\main\ITagRepository;
use models\oauth2\IResourceServerContext;
use Illuminate\Support\Facades\Validator;
use ModelSerializers\SerializerRegistry;
use Illuminate\Support\Facades\Request;
use App\Services\Model\ITagService;
/**
 * Class OAuth2TagsApiController
 * @package App\Http\Controllers
 */
final class OAuth2TagsApiController extends OAuth2ProtectedController
{
    use ParametrizedGetAll;

    use GetAndValidateJsonPayload;

    use RequestProcessor;

    /**
     * @var ITagService
     */
    private $tag_service;

    /**
     * OAuth2TagsApiController constructor.
     * @param ITagService $tag_service
     * @param ITagRepository $tag_repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ITagService $tag_service,
        ITagRepository $tag_repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $tag_repository;
        $this->tag_service = $tag_service;
    }

    public function getAll(){
        return $this->_getAll(
            function () {
                return [
                    'tag' => ['=@', '==', '@@'],
                ];
            },
            function () {
                return [
                    'tag' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'tag',
                    'id',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                $current_member = $this->resource_server_context->getCurrentUser();
                $serializer_type = SerializerRegistry::SerializerType_Public;

                if (!is_null($current_member) && ($current_member->isAdmin() || $current_member->isSummitAdmin())) {
                    $serializer_type = SerializerRegistry::SerializerType_Admin;
                }

                return $serializer_type;
            }
        );
    }

    public function getTag($tag_id){
        return $this->processRequest(function () use ($tag_id) {
            $tag = $this->repository->getById(intval($tag_id));

            if(is_null($tag)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($tag)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    public function addTag(){
        return $this->processRequest(function () {
            if(!Request::isJson()) return $this->error400();
            $data = Request::json();

            $rules = [
                'tag' => 'required|string|max:100',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $tag = $this->tag_service->addTag($data->all());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($tag)->serialize
            (
                Request::input('expand','')
            ));
        });
    }

    public function updateTag($tag_id){
        return $this->processRequest(function () use ($tag_id) {
            if(!Request::isJson()) return $this->error400();
            $data = Request::json();

            $rules = [
                'tag' => 'required|string|max:100',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $tag = $this->tag_service->updateTag(intval($tag_id), $data->all());

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($tag)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    public function deleteTag($tag_id){
        return $this->processRequest(function () use ($tag_id) {

            $this->tag_service->deleteTag(intval($tag_id));

            return $this->deleted();
        });
    }
}
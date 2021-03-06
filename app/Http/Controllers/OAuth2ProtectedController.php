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

use Illuminate\Support\Facades\Request;
use models\oauth2\IResourceServerContext;
use models\utils\IBaseRepository;
/**
 * Class OAuth2ProtectedController
 * OAuth2 Protected Base API
 */
abstract class OAuth2ProtectedController extends JsonController
{

    /**
     * @var IResourceServerContext
     */
    protected $resource_server_context;

    /**
     * @var IBaseRepository
     */
    protected $repository;

    /**
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct(IResourceServerContext $resource_server_context)
    {
        parent::__construct();
        $this->resource_server_context = $resource_server_context;
    }

    /**
     * @return IResourceServerContext
     */
    protected function getResourceServerContext(): IResourceServerContext
    {
        return $this->resource_server_context;
    }

    /**
     * @return IBaseRepository
     */
    protected function getRepository(): IBaseRepository
    {
        return $this->repository;
    }
}
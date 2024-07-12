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
use App\Models\Foundation\Marketplace\IApplianceRepository;
use models\oauth2\IResourceServerContext;

/**
 * Class AppliancesApiController
 * @package App\Http\Controllers
 */
final class AppliancesApiController extends AbstractCompanyServiceApiController {
  /**
   * AppliancesApiController constructor.
   * @param IApplianceRepository $repository
   */
  public function __construct(
    IApplianceRepository $repository,
    IResourceServerContext $resource_server_context,
  ) {
    parent::__construct($repository, $resource_server_context);
  }

  public function getAll() {
    return parent::getAll();
  }
}

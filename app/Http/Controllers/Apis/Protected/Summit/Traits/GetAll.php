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

use App\Http\Utils\Filters\FiltersParams;
use App\ModelSerializers\SerializerUtils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use libs\utils\PaginationValidationRules;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterParser;
use utils\OrderParser;
use utils\PagingInfo;
use Exception;
/**
 * Trait GetAll
 * @package App\Http\Controllers
 */
trait GetAll {
  use BaseSummitAPI;

  /**
   * @return array
   */
  protected function getFilterRules(): array {
    return [];
  }

  /**
   * @return array
   */
  protected function getFilterValidatorRules(): array {
    return [];
  }
  /**
   * @return array
   */
  protected function getOrderRules(): array {
    return [];
  }

  protected function applyExtraFilters(Filter $filter): Filter {
    return $filter;
  }

  protected function serializerType(): string {
    return SerializerRegistry::SerializerType_Public;
  }

  /**
   * @return \Illuminate\Http\JsonResponse|mixed
   */
  public function getAll() {
    $values = Request::all();
    $rules = PaginationValidationRules::get();

    try {
      $validation = Validator::make($values, $rules);

      if ($validation->fails()) {
        $ex = new ValidationException();
        throw $ex->setMessages($validation->messages()->toArray());
      }

      // default values
      $page = 1;
      $per_page = PaginationValidationRules::PerPageMin;

      if (Request::has("page")) {
        $page = intval(Request::input("page"));
        $per_page = intval(Request::input("per_page"));
      }

      $filter = null;

      if (FiltersParams::hasFilterParam()) {
        $filter = FilterParser::parse(FiltersParams::getFilterParam(), $this->getFilterRules());
      }

      if (is_null($filter)) {
        $filter = new Filter();
      }

      $filter_validator_rules = $this->getFilterValidatorRules();
      if (count($filter_validator_rules)) {
        $filter->validate($filter_validator_rules);
      }

      $order = null;

      if (Request::has("order")) {
        $order = OrderParser::parse(Request::input("order"), $this->getOrderRules());
      }

      $data = $this->getRepository()->getAllByPage(
        new PagingInfo($page, $per_page),
        $this->applyExtraFilters($filter),
        $order,
      );

      return $this->ok(
        $data->toArray(
          SerializerUtils::getExpand(),
          SerializerUtils::getFields(),
          SerializerUtils::getRelations(),
          ["serializer_type" => $this->serializerType()],
          $this->serializerType(),
        ),
      );
    } catch (ValidationException $ex1) {
      Log::warning($ex1);
      return $this->error412([$ex1->getMessage()]);
    } catch (EntityNotFoundException $ex2) {
      Log::warning($ex2);
      return $this->error404(["message" => $ex2->getMessage()]);
    } catch (\HTTP401UnauthorizedException $ex3) {
      Log::warning($ex3);
      return $this->error401();
    } catch (Exception $ex) {
      Log::error($ex);
      return $this->error500($ex);
    }
  }
}

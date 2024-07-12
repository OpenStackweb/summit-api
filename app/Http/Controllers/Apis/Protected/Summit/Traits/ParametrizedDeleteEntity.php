<?php namespace App\Http\Controllers;
/**
 * Copyright 2020 OpenStack Foundation
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
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
/**
 * Trait ParametrizedDeleteEntity
 * @package App\Http\Controllers
 */
trait ParametrizedDeleteEntity {
  use BaseAPI;

  /**
   * @param $id
   * @param callable $deleteEntityFn
   * @param mixed ...$args
   * @return mixed
   */
  public function _delete($id, callable $deleteEntityFn, ...$args) {
    try {
      $deleteEntityFn($id, ...$args);
      return $this->deleted();
    } catch (ValidationException $ex) {
      Log::warning($ex);
      return $this->error412([$ex->getMessage()]);
    } catch (EntityNotFoundException $ex) {
      Log::warning($ex);
      return $this->error404(["message" => $ex->getMessage()]);
    } catch (\HTTP401UnauthorizedException $ex) {
      Log::warning($ex);
      return $this->error401();
    } catch (HTTP403ForbiddenException $ex) {
      Log::warning($ex);
      return $this->error403();
    } catch (Exception $ex) {
      Log::error($ex);
      return $this->error500($ex);
    }
  }
}

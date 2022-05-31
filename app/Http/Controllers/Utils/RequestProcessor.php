<?php namespace App\Http\Controllers;
/*
 * Copyright 2022 OpenStack Foundation
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
use models\exceptions\ValidationException;
use models\exceptions\EntityNotFoundException;
use Exception;
use Illuminate\Support\Facades\Log;
use Closure;
/**
 * Trait RequestProcessor
 * @package App\Http\Controllers
 */
trait RequestProcessor
{
    /**
     * @param Closure $callback
     * @return mixed
     */
    public function processRequest(Closure $callback){
        try{
            return $callback($this);
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
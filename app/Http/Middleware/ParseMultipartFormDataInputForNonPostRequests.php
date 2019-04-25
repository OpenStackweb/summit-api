<?php namespace App\Http\Middleware;
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
use Closure;
use utils\ParseMultiPartFormDataInputStream;
/**
 * Class ParseMultipartFormDataInputForNonPostRequests
 * @package App\Http\Middleware
 */
final class ParseMultipartFormDataInputForNonPostRequests
{
    /*
    * Content-Type: multipart/form-data - only works for POST requests. All others fail, this is a bug in PHP since 2011.
    * See comments here: https://github.com/laravel/framework/issues/13457
    *
    * This middleware converts all multi-part/form-data for NON-POST requests, into a properly formatted
    * request variable for Laravel 5.6. It uses the ParseInputStream class, found here:
    * https://gist.github.com/devmycloud/df28012101fbc55d8de1737762b70348
    */
    public function handle($request, Closure $next)
    {
        if ($request->method() == 'POST' OR $request->method() == 'GET') {
            return $next($request);
        }

        if (preg_match('/multipart\/form-data/', $request->headers->get('Content-Type')) or
            preg_match('/multipart\/form-data/', $request->headers->get('content-type'))
        ) {
            $parser     = new ParseMultiPartFormDataInputStream(file_get_contents('php://input'));
            $params     = $parser->getInput();
            $files      = [];
            $parameters = [];
            foreach ($params as $key => $param) {
                if ($param instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                    $files[$key] = $param;
                } else {
                    $parameters[$key] = $param;
                }
            }
            if (count($files) > 0) {
                $request->files->add($files);
            }
            if (count($parameters) > 0) {
                $request->request->add($parameters);
            }
        }
        return $next($request);
    }
}
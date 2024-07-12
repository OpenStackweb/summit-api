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
use App\Http\Utils\CSVExporter;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
/**
 * Class JsonController
 * @package App\Http\Controllers
 */
abstract class JsonController extends Controller {
  public function __construct() {
  }

  protected function error500(Exception $ex) {
    Log::error($ex);

    return Response::json(["message" => "server error"], 500);
  }

  protected function created($data = "ok") {
    $res = Response::json($data, 201);
    //jsonp
    if (Request::has("callback")) {
      $res->setCallback(Request::input("callback"));
    }

    return $res;
  }

  protected function deleted($data = "ok") {
    $res = Response::json($data, 204);
    //jsonp
    if (Request::has("callback")) {
      $res->setCallback(Request::input("callback"));
    }

    return $res;
  }

  protected function updated($data = "ok", $has_content = true) {
    $res = Response::json($data, $has_content ? 201 : 204);
    //jsonp
    if (Request::has("callback")) {
      $res->setCallback(Request::input("callback"));
    }
    return $res;
  }

  /**
   * @param mixed $data
   * @return mixed
   */
  protected function ok($data = "ok") {
    $res = $this->response2XX(200, $data);

    //jsonp
    if (Request::has("callback")) {
      $res->setCallback(Request::input("callback"));
    }

    return $res;
  }

  protected function error400($data = ["message" => "Bad Request"]) {
    return Response::json($data, 400);
  }

  protected function error404($data = ["message" => "Entity Not Found"]) {
    if (!is_array($data)) {
      $data = ["message" => $data];
    }
    return Response::json($data, 404);
  }

  protected function error403($data = ["message" => "Forbidden"]) {
    if (!is_array($data)) {
      $data = ["message" => $data];
    }
    return Response::json($data, 403);
  }

  protected function error401(
    $data = [
      "message" => 'You don\'t have access to this item through the API.',
    ],
  ) {
    if (!is_array($data)) {
      $data = ["message" => $data];
    }
    return Response::json($data, 401);
  }

  protected function response2XX($code = 200, $data = "") {
    return Response::json($data, $code);
  }

  /**
   *  {
   * "message": "Validation Failed",
   * "errors": [
   * {
   * "resource": "Issue",
   * "field": "title",
   * "code": "missing_field"
   * }
   * ]
   * }
   * @param $messages
   * @return mixed
   */
  protected function error412($messages, int $code = 0) {
    if (!is_array($messages)) {
      $messages = [$messages];
    }
    return Response::json(
      [
        "message" => "Validation Failed",
        "errors" => $messages,
        "code" => $code,
      ],
      412,
    );
  }

  /**
   * @param string $format
   * @param string $filename
   * @param array $items
   * @param array $formatters
   * @param array $columns
   * @return \Illuminate\Http\Response
   */
  protected function export(
    $format,
    $filename,
    array $items,
    array $formatters = [],
    array $columns = [],
  ) {
    if ($format == "csv") {
      return $this->csv($filename, $items, $formatters, ",", "application/vnd.ms-excel", $columns);
    }
  }

  /**
   * @param string $filename
   * @param array $items
   * @param array $formatters
   * @param string $field_separator
   * @param string $mime_type
   * @param array $columns
   * @return \Illuminate\Http\Response
   */
  protected function csv(
    $filename,
    array $items,
    array $formatters = [],
    $field_separator = ",",
    $mime_type = "application/vnd.ms-excel",
    array $columns = [],
  ) {
    $headers = [
      "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
      "Content-type" => $mime_type,
      "Content-Transfer-Encoding" => "binary",
      "Content-Disposition" => "attachment; filename=" . $filename . ".csv",
      "Last-Modified: " => gmdate("D, d M Y H:i:s") . " GMT",
      "Expires" => "0",
      "Pragma" => "public",
    ];

    return $this->rawContent(
      CSVExporter::getInstance()->export($items, $field_separator, $formatters),
      $headers,
    );
  }

  /**
   * @param string $body
   * @param array $headers
   * @return \Illuminate\Http\Response
   */
  public function rawContent(string $body, array $headers = []) {
    return Response::make($body, 200, $headers);
  }

  /**
   * @param string $filename
   * @param string $content
   * @return \Illuminate\Http\Response
   */
  protected function pdf(string $filename, string $content) {
    $headers = [
      "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
      "Content-type" => "application/pdf",
      "Content-Transfer-Encoding" => "binary",
      "Content-Disposition" => "attachment; filename=" . basename($filename),
      "Expires" => "0",
      "Last-Modified: " => gmdate("D, d M Y H:i:s") . " GMT",
      "Pragma" => "public",
    ];

    return Response::make($content, 200, $headers);
  }
}

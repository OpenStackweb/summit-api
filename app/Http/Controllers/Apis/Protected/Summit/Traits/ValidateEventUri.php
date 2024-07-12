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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
/**
 * Trait ValidateEventUri
 * @package App\Http\Controllers
 */
trait ValidateEventUri {
  /**
   * @param array $payload
   * @return array
   */
  private function validateEventUri(array $payload) {
    if (!isset($payload["event_uri"]) || empty($payload["event_uri"])) {
      Log::debug("validateEventUri: event uri not set , trying to get from Referer");
      $payload["event_uri"] = Request::instance()->header("Referer", null);
    }

    if (isset($payload["event_uri"]) && !empty($payload["event_uri"])) {
      $allowed_return_uris = $this->resource_server_context->getAllowedReturnUris();
      if (!empty($allowed_return_uris)) {
        Log::debug(
          sprintf(
            "validateEventUri: event_uri %s allowed_return_uris %s",
            $payload["event_uri"],
            $allowed_return_uris,
          ),
        );
        // validate the event_uri against the allowed returned uris of the current client
        // check using host name
        $test_host = parse_url($payload["event_uri"], PHP_URL_HOST);
        $valid_event_uri = false;
        foreach (explode(",", $allowed_return_uris) as $allowed_uri) {
          if ($test_host == parse_url($allowed_uri, PHP_URL_HOST)) {
            $valid_event_uri = true;
            Log::debug(sprintf("validateEventUri: valid host %s", $test_host));
            break;
          }
        }
        if (!$valid_event_uri) {
          unset($payload["event_uri"]);
        }
      }
    }

    return $payload;
  }
}

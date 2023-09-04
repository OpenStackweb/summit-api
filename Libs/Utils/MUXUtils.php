<?php namespace libs\utils;

/*
 * Copyright 2023 OpenStack Foundation
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

/**
 * Class MUXUtils
 * @package libs\utils
 */
final class MUXUtils
{
    const MUX_STREAM_REGEX = '/https\:\/\/stream\.mux\.com\/(.*)\.m3u8/';

    /**
     * @param string $url
     * @return string|null
     */
    public static function getPlaybackId(string $url):?string{
        if (!preg_match(self::MUX_STREAM_REGEX, $url, $matches)) {
            return null;
        }
        if(count($matches) < 2){
            return null;
        }
        return $matches[1];
    }
}
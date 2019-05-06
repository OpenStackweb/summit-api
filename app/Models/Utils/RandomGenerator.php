<?php namespace models\utils;
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
use Exception;
/**
 * Class RandomGenerator
 * @package models\utils
 */
final class RandomGenerator {

    /**
     * Note: Returned values are not guaranteed to be crypto-safe,
     * depending on the used retrieval method.
     *
     * @return string Returns a random series of bytes
     */
    public function generateEntropy() {
        $isWin = preg_match('/WIN/', PHP_OS);

        // TODO Fails with "Could not gather sufficient random data" on IIS, temporarily disabled on windows
        if(!$isWin) {
            if(function_exists('random_bytes')) {
                $e = random_bytes(64);
                if($e !== false) return $e;
            }
        }

        // Fall back to SSL methods - may slow down execution by a few ms
        if (function_exists('openssl_random_pseudo_bytes')) {
            $e = openssl_random_pseudo_bytes(64, $strong);
            // Only return if strong algorithm was used
            if($strong) return $e;
        }

        // Read from the unix random number generator
        if(!$isWin && !ini_get('open_basedir') && is_readable('/dev/urandom') && ($h = fopen('/dev/urandom', 'rb'))) {
            $e = fread($h, 64);
            fclose($h);
            return $e;
        }

        // Warning: Both methods below are considered weak

        // try to read from the windows RNG
        if($isWin && class_exists('COM')) {
            try {
                $comObj = new COM('CAPICOM.Utilities.1');

                if(is_callable(array($comObj,'GetRandom'))) {
                    return  base64_decode($comObj->GetRandom(64, 0));
                }
            } catch (Exception $ex) {
            }
        }

        // Fallback to good old mt_rand()
        return uniqid(mt_rand(), true);
    }

    /**
     * Generates a random token that can be used for session IDs, CSRF tokens etc., based on
     * hash algorithms.
     *
     * If you are using it as a password equivalent (e.g. autologin token) do NOT store it
     * in the database as a plain text but encrypt it with Member::encryptWithUserSettings.
     *
     * @param String $algorithm Any identifier listed in hash_algos() (Default: whirlpool)
     *
     * @return String Returned length will depend on the used $algorithm
     */
    public function randomToken($algorithm = 'whirlpool') {
        return hash($algorithm, $this->generateEntropy());
    }
}

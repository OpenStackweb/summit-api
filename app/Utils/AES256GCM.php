<?php namespace App\Utils;
/*
 * Copyright 2024 OpenStack Foundation
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
 * Class AES256GCM
 * @package App\Utils
 */
final class AES256GCM
{

    const CIPHER = 'AES-256-GCM';

    /**
     * Encoded/Decoded data
     *
     * @var null|string
     */
    protected $data;
    /**
     * Initialization vector value
     *
     * @var string
     */
    protected $iv;
    /**
     * Error message if operation failed
     *
     * @var null|string
     */
    protected $errorMessage;

    /**
     * AesCipher constructor.
     *
     * @param string $iv Initialization vector value
     * @param string|null $data Encoded/Decoded data
     * @param string|null $errorMessage Error message if operation failed
     */
    public function __construct(string $iv, string $data = null, string $errorMessage = null)
    {
        $this->iv = $iv;
        $this->data = $data;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @param string $key
     * @param string $data
     * @return AES256GCM
     */
    public static function encrypt(string $key, string $data): AES256GCM
    {
        try {
            // Check secret length
            if (!AES256GCM::isKeyLengthValid($key)) {
                throw new \InvalidArgumentException("Secret key's length must be 128, 192 or 256 bits");
            }

            $iv_len = openssl_cipher_iv_length(AES256GCM::CIPHER);
            // Get random initialization vector
            $iv = random_bytes($iv_len);
            $tag = '';
            // Encrypt input text
            $raw = openssl_encrypt(
                $data,
                AES256GCM::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '',
                16
            );

            // Return base64-encoded string: initVector + encrypted result
            $result = base64_encode($iv) . base64_encode($raw . $tag);

            if ($result === false) {
                // Operation failed
                return new AES256GCM($iv, null, openssl_error_string());
            }

            // Return successful encoded object
            return new AES256GCM($iv, $result);
        } catch (\Exception $e) {
            // Operation failed
            return new AES256GCM(isset($iv), null, $e->getMessage());
        }
    }

    /**
     * @param string $key
     * @param string $data
     * @return AES256GCM
     */
    public static function decrypt(string $key, string $data): AES256GCM
    {
        try {
            // Check secret length
            if (!AES256GCM::isKeyLengthValid($key)) {
                throw new \InvalidArgumentException("Secret key's length must be 128, 192 or 256 bits");
            }

            $iv = base64_decode(substr($data, 0, 16));
            $data = base64_decode(substr($data, 16));
            $tag = substr($data, strlen($data) - 16);
            $data = substr($data, 0, strlen($data) - 16);

            // Trying to get decrypted text
            $decoded = openssl_decrypt(
                $data,
                AES256GCM::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($decoded === false) {
                // Operation failed
                return new AES256GCM(isset($iv), null, openssl_error_string());
            }

            // Return successful decoded object
            return new AES256GCM($iv, $decoded);
        } catch (\Exception $e) {
            // Operation failed
            return new AES256GCM(isset($iv), null, $e->getMessage());
        }
    }

    /**
     * Check that secret password length is valid
     *
     * @param string $key 16/24/32 -characters secret password
     *
     * @return bool
     */
    public static function isKeyLengthValid(string $key): bool
    {
        $length = strlen($key);

        return $length == 16 || $length == 24 || $length == 32;
    }

    /**
     * Get encoded/decoded data
     *
     * @return string|null
     */
    public function getData(): ?string
    {
        return $this->data;
    }

    /**
     * Get initialization vector value
     *
     * @return string|null
     */
    public function getInitVector(): ?string
    {
        return $this->iv;
    }

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Check that operation failed
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->errorMessage !== null;
    }

    /**
     * To string return resulting data
     *
     * @return null|string
     */
    public function __toString(): ?string
    {
        return $this->getData();
    }
}
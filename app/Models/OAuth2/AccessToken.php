<?php namespace models\oauth2;
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

/**
 * Class AccessToken
 * http://tools.ietf.org/html/rfc6749#section-1.4
 * @package oauth2\models
 */
final class AccessToken extends Token
{
    /**
     * @var
     */
    private $auth_code;

    /**
     * @var
     */
    private $refresh_token;

    /**
     * @var string
     */
    private $allowed_origins;

    /**
     * @var string
     */
    private $allowed_return_uris;

    /**
     * @var string
     */
    private $application_type;

    public function __construct()
    {
        parent::__construct(72);
    }

    /**
     * @var null|int
     */
    private $user_external_id;

    /**
     * @var string|null
     */
    private $user_identifier;

    /**
     * @var string|null
     */
    private $user_email;

    /**
     * @var string|null
     */
    private $user_first_name;

    /**
     * @var string|null
     */
    private $user_last_name;

    /**
     * @var array
     */
    private $user_groups;

    private static function getValueFromInfo(string $key, array $token_info){
        return isset($token_info[$key])? $token_info[$key] :null;
    }
    /**
     * @param array $token_info
     * @return AccessToken
     */
    public static function createFromParams(array $token_info) {
        $instance                      = new self();
        $instance->value               = $token_info['access_token'];
        $instance->scope               = $token_info['scope'];
        $instance->client_id           = $token_info['client_id'];
        $instance->user_id             = self::getValueFromInfo('user_id', $token_info);
        $instance->user_external_id    = self::getValueFromInfo('user_external_id', $token_info);
        $instance->user_identifier     = self::getValueFromInfo('user_identifier', $token_info);
        $instance->user_email          = self::getValueFromInfo('user_email', $token_info);
        $instance->user_first_name     = self::getValueFromInfo('user_first_name', $token_info);
        $instance->user_last_name      = self::getValueFromInfo('user_last_name', $token_info);
        $instance->auth_code           = null;
        $instance->audience            = $token_info['audience'];
        $instance->refresh_token       = null;
        $instance->lifetime            = intval($token_info['expires_in']);
        $instance->is_hashed           = false;
        $instance->allowed_return_uris = self::getValueFromInfo('allowed_return_uris', $token_info);
        $instance->application_type    = $token_info['application_type'];
        $instance->allowed_origins     = self::getValueFromInfo('allowed_origins', $token_info);
        $instance->user_groups         = self::getValueFromInfo('user_groups', $token_info);
        if(!empty($instance->user_groups)) {
            if(is_string($instance->user_groups))
                $instance->user_groups = json_decode($instance->user_groups, true);
        }
        else
        {
            $instance->user_groups = [];
        }
        return $instance;
    }

    public function getAuthCode()
    {
        return $this->auth_code;
    }

    public function getRefreshToken()
    {
        return $this->refresh_token;
    }

    public function getApplicationType()
    {
        return $this->application_type;
    }

    public function getAllowedOrigins()
    {
        return $this->allowed_origins;
    }

    public function getAllowedReturnUris()
    {
        return $this->allowed_return_uris;
    }

    /**
     * @return int|null
     */
    public function getUserExternalId()
    {
        return $this->user_external_id;
    }

    public function toJSON()
    {
        return '{}';
    }

    public function fromJSON($json)
    {

    }

    /**
     * @return null|string
     */
    public function getUserIdentifier(): ?string
    {
        return $this->user_identifier;
    }

    /**
     * @return null|string
     */
    public function getUserEmail(): ?string
    {
        return $this->user_email;
    }

    /**
     * @return null|string
     */
    public function getUserFirstName(): ?string
    {
        return $this->user_first_name;
    }

    /**
     * @return null|string
     */
    public function getUserLastName(): ?string
    {
        return $this->user_last_name;
    }

    /**
     * @return array
     */
    public function getUserGroups():array {
        return $this->user_groups;
    }
}
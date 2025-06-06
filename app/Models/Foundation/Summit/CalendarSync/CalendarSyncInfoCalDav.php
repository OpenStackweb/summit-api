<?php namespace models\summit\CalendarSync;
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
use Doctrine\ORM\Mapping AS ORM;
use services\utils\Facades\Encryption;
/**
 * Class CalendarSyncInfoCalDav
 * @package models\summit\CalendarSync
 */
#[ORM\Table(name: 'CalendarSyncInfoCalDav')]
#[ORM\Entity]
class CalendarSyncInfoCalDav extends CalendarSyncInfo
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'UserName', type: 'string')]
    protected $user_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'UserPassword', type: 'string')]
    protected $user_password;

    /**
     * @var string
     */
    #[ORM\Column(name: 'UserPrincipalURL', type: 'string')]
    protected $user_principal_url;

    /**
     * @var string
     */
    #[ORM\Column(name: 'CalendarDisplayName', type: 'string')]
    protected $calendar_display_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'CalendarSyncToken', type: 'string')]
    protected $calendar_sync_token;

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @param string $user_name
     */
    public function setUserName($user_name)
    {
        $this->user_name = $user_name;
    }

    /**
     * @return string
     */
    public function getUserPassword()
    {
        return Encryption::decrypt($this->user_password);
    }

    /**
     * @param string $user_password
     */
    public function setUserPassword($user_password)
    {
        $this->user_password = Encryption::encrypt($user_password);
    }

    /**
     * @return string
     */
    public function getUserPrincipalUrl()
    {
        return $this->user_principal_url;
    }

    /**
     * @param string $user_principal_url
     */
    public function setUserPrincipalUrl($user_principal_url)
    {
        $this->user_principal_url = $user_principal_url;
    }

    /**
     * @return string
     */
    public function getServer(){
        $result = parse_url($this->user_principal_url);

        if(!$result) throw new \InvalidArgumentException(sprintf("user_principal_url %s is invalid", $this->user_principal_url));
        if(!isset($result['scheme']))
            throw new \InvalidArgumentException(sprintf("user_principal_url %s is invalid", $this->user_principal_url));
        if(!isset($result['host']))
            throw new \InvalidArgumentException(sprintf("user_principal_url %s is invalid", $this->user_principal_url));

        return $result['scheme']."://".$result['host'];
    }

    /**
     * @return string
     */
    public function getCalendarUrl(){
        return $this->external_id;
    }

    /**
     * @return string
     */
    public function getCalendarDisplayName()
    {
        return $this->calendar_display_name;
    }

    /**
     * @param string $calendar_display_name
     */
    public function setCalendarDisplayName($calendar_display_name)
    {
        $this->calendar_display_name = $calendar_display_name;
    }

    /**
     * @return string
     */
    public function getCalendarSyncToken()
    {
        return $this->calendar_sync_token;
    }

    /**
     * @param string $calendar_sync_token
     */
    public function setCalendarSyncToken($calendar_sync_token)
    {
        $this->calendar_sync_token = $calendar_sync_token;
    }
}
<?php namespace ModelSerializers;
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
use models\main\Member;
/**
 * Class PublicMemberSerializer
 * @package ModelSerializers
 */
final class PublicMemberSerializer extends AbstractMemberSerializer
{

    /**
     * @param Member $member
     * @param array $values
     * @return array
     */
    protected function checkDataPermissions(Member $member, array $values):array{
        if(!$member->isPublicProfileShowBio())
        {
            unset($values['bio']);
            unset($values['gender']);
            unset($values['company']);
            unset($values['state']);
            unset($values['country']);
        }

        if(!$member->isPublicProfileShowSocialMediaInfo())
        {
            unset($values['github_user']);
            unset($values['linked_in']);
            unset($values['irc']);
            unset($values['twitter']);
        }

        if(!$member->isPublicProfileShowPhoto())
        {
            unset($values['pic']);
        }

        if(!$member->isPublicProfileShowFullname())
        {
            unset($values['first_name']);
            unset($values['last_name']);
        }
        return $values;
    }

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $member  = $this->object;
        if(!$member instanceof Member) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        return $this->checkDataPermissions($member, $values);
    }
}
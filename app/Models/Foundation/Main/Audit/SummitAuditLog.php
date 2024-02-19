<?php namespace models\main;
/**
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

use Doctrine\ORM\Mapping AS ORM;
use models\summit\Summit;
use models\summit\SummitOwned;

/**
 * @ORM\Entity
 * @ORM\Table(name="SummitAuditLog")
 * Class SummitAuditLog
 * @package models\main
 */
class SummitAuditLog extends AuditLog
{
    use SummitOwned;

    const ClassName = 'SummitAuditLog';

    public function getClassName(): string
    {
        return self::ClassName;
    }

    public function __construct(?Member $user, string $action, ?Summit $summit)
    {
        parent::__construct($user, $action);
        $this->summit = $summit;
    }
}
<?php namespace App\Models\Foundation\Summit\Events\RSVP;
/**
 * Copyright 2018 OpenStack Foundation
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
/**
 * @package App\Models\Foundation\Summit\Events\RSVP
 */
#[ORM\Table(name: 'RSVPLiteralContentQuestionTemplate')]
#[ORM\Entity] // Class RSVPLiteralContentQuestionTemplate
class RSVPLiteralContentQuestionTemplate extends RSVPQuestionTemplate
{
    const ClassName = 'RSVPLiteralContentQuestionTemplate';
    /**
     * @var string
     */
    #[ORM\Column(name: 'Content', type: 'string')]
    protected $content;

    /**
     * @return string
     */
    public function getClassName(){
        return self::ClassName;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    public static $metadata = [
        'content'    => 'string',
        'class_name' => self::ClassName,
    ];

    /**
     * @return array
     */
    public static function getMetadata(){
        return array_merge(RSVPQuestionTemplate::getMetadata(), self::$metadata);
    }

}
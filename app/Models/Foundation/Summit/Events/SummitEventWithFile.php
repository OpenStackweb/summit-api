<?php namespace models\summit;
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
use models\main\File;

/**
 * Class SummitEventWithFile
 * @package models\summit
 */
#[ORM\Table(name: 'SummitEventWithFile')]
#[ORM\Entity]
class SummitEventWithFile extends SummitEvent
{
    /**
     * @return string
     */
    public function getClassName():string{
        return "SummitEventWithFile";
    }

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'AttachmentID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist'])]
    private $attachment;

    /**
     * @return File
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param File $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * @return bool
     */
    public function hasAttachment(){
        return $this->getAttachmentId() > 0;
    }

    /**
     * @return int
     */
    public function getAttachmentId(){
        try{
            return !is_null($this->attachment)?$this->attachment->getId():0;
        }
        catch(\Exception $ex){
            return 0;
        }
    }
}
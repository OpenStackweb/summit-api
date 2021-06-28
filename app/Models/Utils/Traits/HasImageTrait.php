<?php namespace App\Models\Utils\Traits;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\main\File;
/**
 * Trait HasImageTrait
 * @package App\Models\Utils\Traits
 */
trait HasImageTrait
{
    /**
     * @param File $image
     */
    public function setImage(File $image): void
    {
        $this->image = $image;
    }

    public function clearImage():void{
        $this->image = null;
    }

    /**
     * @return bool
     */
    public function hasImage():bool{
        return $this->getImageId() > 0;
    }

    /**
     * @return int
     */
    public function getImageId():int
    {
        try{
            if(is_null($this->image)) return 0;
            return $this->image->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    public function getImage():?File{
        return $this->image;
    }

    /**
     * @return string|null
     */
    public function getImageUrl():?string{
        $photoUrl = null;
        if($this->hasImage() && $photo = $this->getImage()){
            $photoUrl =  $photo->getUrl();
        }
        return $photoUrl;
    }
}
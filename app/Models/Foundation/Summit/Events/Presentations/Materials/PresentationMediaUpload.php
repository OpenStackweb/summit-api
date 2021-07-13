<?php namespace models\summit;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Models\Utils\IStorageTypesConstants;
use Doctrine\ORM\Mapping AS ORM;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * @ORM\Entity
 * @ORM\Table(name="PresentationMediaUpload")
 * Class PresentationMediaUpload
 * @package models\summit
 */
class PresentationMediaUpload extends PresentationMaterial
{
    /**
     * @return string
     */
    public function getClassName(){
        return 'PresentationMediaUpload';
    }

    /**
     * @ORM\Column(name="FileName", type="string")
     * @var string
     */
    private $filename;

    /**
     * @ORM\Column(name="LegacyPathFormat", type="boolean")
     * @var boolean
     */
    private $legacy_path_format;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitMediaUploadType")
     * @ORM\JoinColumn(name="SummitMediaUploadTypeID", referencedColumnName="ID")
     * @var SummitMediaUploadType
     */
    protected $media_upload_type;

    /**
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * @return SummitMediaUploadType|null
     */
    public function getMediaUploadType(): ?SummitMediaUploadType
    {
        return $this->media_upload_type;
    }

    /**
     * @param SummitMediaUploadType $media_upload_type
     */
    public function setMediaUploadType(SummitMediaUploadType $media_upload_type): void
    {
        $this->media_upload_type = $media_upload_type;
    }

    /**
     * @return bool
     */
    public function isMandatory():bool{
        return $this->media_upload_type->isMandatory();
    }

    /**
     * @return int
     */
    public function getMediaUploadTypeId(){
        try {
            return is_null($this->media_upload_type) ? 0 : $this->media_upload_type->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasMediaUploadType():bool{
        return $this->getMediaUploadTypeId() > 0;
    }

    public function clearMediaUploadType(){
        $this->media_upload_type = null;
    }

    /**
     * @param string $storageType
     * @param string|null $mountingFolder
     * @return string
     */
    public function getRelativePath(string $storageType = IStorageTypesConstants::PublicType, ?string $mountingFolder = null):string {
        return sprintf('%s/%s', $this->getPath($storageType, $mountingFolder), $this->getFilename());
    }

    /**
     * @param string $storageType
     * @param string|null $mountingFolder
     * @return string
     */
    public function getPath(string $storageType = IStorageTypesConstants::PublicType, ?string $mountingFolder = null): string {

        if(empty($mountingFolder))
            $mountingFolder = Config::get('mediaupload.mounting_folder');

        Log::debug(sprintf("PresentationMediaUpload::getPath storageType %s mountingFolder %s", $storageType, $mountingFolder));

        $summit = $this->getPresentation()->getSummit();
        $presentation = $this->getPresentation();
        $format = $storageType == IStorageTypesConstants::PublicType ? '%s/%s/%s': '%s/'.IStorageTypesConstants::PrivateType.'/%s/%s';

        if($this->legacy_path_format)
            return sprintf($format, $mountingFolder, $summit->getId(), $presentation->getId());

        $presentation->generateSlug();
        return sprintf($format, $mountingFolder, sprintf("%s-%s", $summit->getId(), filter_var($summit->getRawSlug(), FILTER_SANITIZE_ENCODED)), sprintf("%s-%s", $presentation->getId(), filter_var($presentation->getSlug(), FILTER_SANITIZE_ENCODED)));
    }

    public function __construct()
    {
        parent::__construct();
        $this->legacy_path_format = true;
    }

}
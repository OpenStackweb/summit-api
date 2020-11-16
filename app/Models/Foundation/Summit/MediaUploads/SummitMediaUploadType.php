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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitMediaUploadTypeRepository")
 * @ORM\Table(name="SummitMediaUploadType")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="media_upload_types"
 *     )
 * })
 * Class SummitMediaUploadType
 * @package models\summit
 */
class SummitMediaUploadType extends SilverstripeBaseModel
{
    use SummitOwned;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitMediaFileType")
     * @ORM\JoinColumn(name="TypeID", referencedColumnName="ID")
     * @var SummitMediaFileType
     */
    protected $type;

    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="Description", type="string")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="MaxSize", type="integer")
     * @var int
     * in KB
     */
    private $max_size;

    /**
     * @ORM\Column(name="IsMandatory", type="boolean")
     * @var bool
     */
    private $is_mandatory;

    /**
     * @ORM\Column(name="PrivateStorageType", type="string")
     * @var string
     */
    private $private_storage_type;

    /**
     * @ORM\Column(name="PublicStorageType", type="string")
     * @var string
     */
    private $public_storage_type;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\PresentationType", inversedBy="allowed_media_upload_types", cascade={"persist"})
     * @ORM\JoinTable(name="PresentationType_SummitMediaUploadType",
     *      joinColumns={@ORM\JoinColumn(name="SummitMediaUploadTypeID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="PresentationTypeID", referencedColumnName="ID")}
     *      )
     * @var PresentationType[]
     */
    private $presentation_types;

    /**
     * SummitMediaUploadType constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->is_mandatory = false;
        $this->max_size = 0;
        $this->presentation_types = new ArrayCollection();
        $this->public_storage_type = IStorageTypesConstants::None;
        $this->private_storage_type = IStorageTypesConstants::None;
    }

    public function setType(SummitMediaFileType $type){
        $this->type = $type;
    }

    /**
     * @return SummitMediaFileType
     */
    public function getType(): ?SummitMediaFileType{
        return $this->type;
    }

    /**
     * @return int
     */
    public function getTypeId(){
        try {
            return is_null($this->type) ? 0 : $this->type->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasType():bool{
        return $this->getTypeId() > 0;
    }

    public function clearType(){
        $this->type = null;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getMaxSize(): int
    {
        return $this->max_size;
    }

    /**
     * @return int
     */
    public function getMaxSizeMB(): int
    {
        return $this->max_size/1024;
    }

    /**
     * @param int $max_size
     */
    public function setMaxSize(int $max_size): void
    {
        $this->max_size = $max_size;
    }

    /**
     * @return bool
     */
    public function isMandatory(): bool
    {
        return $this->is_mandatory;
    }

    public function markAsMandatory(): void
    {
        $this->is_mandatory = true;
    }

    public function markAsOptional():void{
        $this->is_mandatory = false;
    }

    /**
     * @return string
     */
    public function getPrivateStorageType(): ?string
    {
        return $this->private_storage_type;
    }

    /**
     * @param string $private_storage_type
     */
    public function setPrivateStorageType(string $private_storage_type): void
    {
        if(!in_array($private_storage_type, IStorageTypesConstants::ValidPrivateTypes))
            throw new ValidationException(sprintf("invalid private storage type %s", $private_storage_type));
        $this->private_storage_type = $private_storage_type;
    }

    /**
     * @return string
     */
    public function getPublicStorageType(): ?string
    {
        return $this->public_storage_type;
    }

    /**
     * @param string $public_storage_type
     */
    public function setPublicStorageType(string $public_storage_type): void
    {
        if(!in_array($public_storage_type, IStorageTypesConstants::ValidPublicTypes))
            throw new ValidationException(sprintf("invalid public storage type %s", $public_storage_type));

        $this->public_storage_type = $public_storage_type;
    }

    /**
     * @param PresentationType $presentationType
     */
    public function addPresentationType(PresentationType $presentationType){
        if($this->presentation_types->contains($presentationType)) return;
        $this->presentation_types->add($presentationType);
        $presentationType->addAllowedMediaUploadType($this);
    }

    /**
     * @param PresentationType $presentationType
     */
    public function removePresentationType(PresentationType $presentationType){
        if(!$this->presentation_types->contains($presentationType)) return;
        $this->presentation_types->removeElement($presentationType);
        $presentationType->removeAllowedMediaUploadType($this);
    }

    public function getPresentationTypes(){
        return $this->presentation_types;
    }

    public function isPresentationTypeAllowed(SummitEventType $type):bool {
        return $this->presentation_types->contains($type);
    }

    public function clearPresentationTypes():void{
        $this->presentation_types->clear();
    }

    /**
     * @param string $ext
     * @return bool
     */
    public function isValidExtension(string $ext):bool {
        return in_array(strtoupper($ext), explode('|', $this->type->getAllowedExtensions()));
    }

    public function getValidExtensions(){
        return $this->type->getAllowedExtensions();
    }

    public function hasStorageSet():bool {
        return  ($this->private_storage_type != IStorageTypesConstants::None ||  $this->public_storage_type != IStorageTypesConstants::None);
    }

    public function isVideo():bool{
        return str_contains(strtolower($this->getType()->getName()), "video");
    }

}
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
     * @deprecated
     */
    private $is_mandatory;

    /**
     * @ORM\Column(name="MinUploadsQty", type="integer")
     * @var int
     */
    private $min_uploads_qty;

    /**
     * @ORM\Column(name="MaxUploadsQty", type="integer")
     * @var int
     */
    private $max_uploads_qty;

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
     * @ORM\Column(name="UseTemporaryLinksOnPublicStorage", type="boolean")
     * @var bool
     */
    private $use_temporary_links_on_public_storage;

    /**
     * @ORM\Column(name="TemporaryLinksOnPublicStorageTTL", type="integer")
     * @var int
     * in minutes
     */
    private $temporary_links_public_storage_ttl;


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
     * @ORM\OneToMany(targetEntity="models\summit\PresentationMediaUpload", mappedBy="media_upload_type", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $media_uploads;

    /**
     * SummitMediaUploadType constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->is_mandatory = false;
        $this->max_size = 0;
        $this->min_uploads_qty = 0;
        $this->max_uploads_qty = 0;
        $this->presentation_types = new ArrayCollection();
        $this->public_storage_type = IStorageTypesConstants::None;
        $this->private_storage_type = IStorageTypesConstants::None;
        $this->use_temporary_links_on_public_storage = false;
        $this->temporary_links_public_storage_ttl = 10;
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
     * @return int
     */
    public function getMinUploadsQty(): int
    {
        return $this->min_uploads_qty;
    }

    /**
     * @param int $min_uploads_qty
     * @throws ValidationException
     */
    public function setMinUploadsQty(int $min_uploads_qty): void
    {
        if($min_uploads_qty < 0)
            throw new ValidationException("min_uploads_qty should be greater than zero.");

        $this->min_uploads_qty = $min_uploads_qty;
    }

    /**
     * @return int
     *
     * 0 -> INFINITE
     */
    public function getMaxUploadsQty(): int
    {
        return $this->max_uploads_qty;
    }

    /**
     * @param int $max_uploads_qty
     * @throws ValidationException
     */
    public function setMaxUploadsQty(int $max_uploads_qty): void
    {
        if($max_uploads_qty < 0)
            throw new ValidationException("max_uploads_qty should be greater than zero.");

        if($max_uploads_qty > 0 && $this->min_uploads_qty > 0){
            // is not infinite, then should be greater or equal to min
            if($max_uploads_qty < $this->min_uploads_qty){
                throw new ValidationException("max_uploads_qty should be greater than min_uploads_qty.");
            }
        }
        $this->max_uploads_qty = $max_uploads_qty;
    }

    /**
     * @return bool
     */
    public function isMandatory(): bool
    {
        return $this->min_uploads_qty > 0;
    }

    /**
     * @deprecated use SummitMediaUploadType::setMinUploadsQty(1) instead
     */
    public function markAsMandatory(): void
    {
        $this->is_mandatory = true;
    }

    /**
     * @deprecated use SummitMediaUploadType::setMinUploadsQty(0) instead
     */
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

    /**
     * @return bool
     */
    public function hasPublicStorageSet():bool{
        return $this->public_storage_type != IStorageTypesConstants::None;
    }

    public function isVideo():bool{
        return str_contains(strtolower($this->getType()->getName()), "video");
    }

    /**
     * @return mixed
     */
    public function getMediaUploadsToDisplayOnSite(){
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('display_on_site', true));
        return $this->media_uploads->matching($criteria);
    }

    /**
     * @return bool
     */
    public function isUseTemporaryLinksOnPublicStorage(): bool
    {
        return $this->use_temporary_links_on_public_storage;
    }

    /**
     * @param bool $use_temporary_links_on_public_storage
     */
    public function setUseTemporaryLinksOnPublicStorage(bool $use_temporary_links_on_public_storage): void
    {
        $this->use_temporary_links_on_public_storage = $use_temporary_links_on_public_storage;
    }

    /**
     * @return int
     */
    public function getTemporaryLinksPublicStorageTtl(): int
    {
        return $this->temporary_links_public_storage_ttl;
    }

    /**
     * @param int $temporary_links_public_storage_ttl
     */
    public function setTemporaryLinksPublicStorageTtl(int $temporary_links_public_storage_ttl): void
    {
        $this->temporary_links_public_storage_ttl = $temporary_links_public_storage_ttl;
    }

}
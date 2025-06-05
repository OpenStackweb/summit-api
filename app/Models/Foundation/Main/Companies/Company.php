<?php namespace models\main;
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
use App\Models\Foundation\Main\ICompanyMemberLevel;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\main
 */
#[ORM\Table(name: 'Company')]
#[ORM\Entity(repositoryClass: \repositories\main\DoctrineCompanyRepository::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'sponsors_region')] // Class Company
class Company extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'URL', type: 'string')]
    private $url;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'DisplayOnSite', type: 'boolean')]
    private $display_on_site;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'Featured', type: 'boolean')]
    private $featured;

    /**
     * @var string
     */
    #[ORM\Column(name: 'City', type: 'string')]
    private $city;

    /**
     * @var string
     */
    #[ORM\Column(name: 'State', type: 'string')]
    private $state;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Country', type: 'string')]
    private $country;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string')]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Industry', type: 'string')]
    private $industry;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Products', type: 'string')]
    private $products;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Contributions', type: 'string')]
    private $contributions;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ContactEmail', type: 'string')]
    private $contact_email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'MemberLevel', type: 'string')]
    private $member_level;

    /**
     * @var string
     */
    #[ORM\Column(name: 'AdminEmail', type: 'string')]
    private $admin_email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Color', type: 'string')]
    private $color;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Overview', type: 'string')]
    private $overview;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Commitment', type: 'string')]
    private $commitment;

    /**
     * @var string
     */
    #[ORM\Column(name: 'CommitmentAuthor', type: 'string')]
    private $commitment_author;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'isDeleted', type: 'boolean')]
    private $is_deleted;

    /**
     * @var string
     */
    #[ORM\Column(name: 'URLSegment', type: 'string')]
    private $url_segment;

    // relations
    #[ORM\ManyToMany(targetEntity: \models\summit\SummitEvent::class, mappedBy: 'sponsors')]
    private $sponsorships;

    /**
     * @var SupportingCompany[]
     */
    #[ORM\OneToMany(targetEntity: \SupportingCompany::class, mappedBy: 'company', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['order' => 'ASC'])]
    private $project_sponsorships;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'LogoID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist', 'remove'])]
    private $logo;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'BigLogoID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, cascade: ['persist', 'remove'])]
    private $big_logo;

    /**
     * Company constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->sponsorships = new ArrayCollection();
        $this->project_sponsorships = new ArrayCollection();
        $this->featured = false;
        $this->display_on_site = false;
        $this->is_deleted = false;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    /**
     * @param string $industry
     */
    public function setIndustry(string $industry): void
    {
        $this->industry = $industry;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return File
     */
    public function getLogo(): File
    {
        return $this->logo;
    }

    /**
     * @param File $logo
     */
    public function setLogo(File $logo): void
    {
        $this->logo = $logo;
    }

    public function clearLogo():void{
        $this->logo = null;
    }

    /**
     * @return File
     */
    public function getBigLogo(): File
    {
        return $this->big_logo;
    }

    /**
     * @param File $big_logo
     */
    public function setBigLogo(File $big_logo): void
    {
        $this->big_logo = $big_logo;
    }

    public function clearBigLogo():void{
        $this->big_logo = null;
    }

    /**
     * @return bool
     */
    public function hasLogo()
    {
        return $this->getLogoId() > 0;
    }

    /**
     * @return int
     */
    public function getLogoId()
    {
        try {
            if (is_null($this->logo)) return 0;
            return $this->logo->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasBigLogo()
    {
        return $this->getBigLogoId() > 0;
    }

    /**
     * @return int
     */
    public function getBigLogoId()
    {
        try {
            if (is_null($this->big_logo)) return 0;
            return $this->big_logo->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return string|null
     */
    public function getLogoUrl(): ?string
    {

        $logoUrl = null;
        try {
            if ($this->hasLogo() && $logo = $this->getLogo()) {
                $logoUrl = $logo->getUrl();
            }
        }
        catch(\Exception $ex){
            Log::warning($ex);
        }
        return $logoUrl;
    }

    /**
     * @return string|null
     */
    public function getBigLogoUrl(): ?string
    {
        $logoUrl = null;
        try {
            if ($this->hasBigLogo() && $logo = $this->getBigLogo()) {
                $logoUrl = $logo->getUrl();
            }
        }
        catch(\Exception $ex){
            Log::warning($ex);
        }
        return $logoUrl;
    }

    /**
     * @return bool
     */
    public function isDisplayOnSite(): bool
    {
        return $this->display_on_site;
    }

    /**
     * @param bool $display_on_site
     */
    public function setDisplayOnSite(bool $display_on_site): void
    {
        $this->display_on_site = $display_on_site;
    }

    /**
     * @return bool
     */
    public function isFeatured(): bool
    {
        return $this->featured;
    }

    /**
     * @param bool $featured
     */
    public function setFeatured(bool $featured): void
    {
        $this->featured = $featured;
    }

    /**
     * @return string
     */
    public function getProducts(): ?string
    {
        return $this->products;
    }

    /**
     * @param string $products
     */
    public function setProducts(string $products): void
    {
        $this->products = $products;
    }

    /**
     * @return string
     */
    public function getContributions(): ?string
    {
        return $this->contributions;
    }

    /**
     * @param string $contributions
     */
    public function setContributions(string $contributions): void
    {
        $this->contributions = $contributions;
    }

    /**
     * @return string
     */
    public function getContactEmail(): ?string
    {
        return $this->contact_email;
    }

    /**
     * @param string $contact_email
     */
    public function setContactEmail(string $contact_email): void
    {
        $this->contact_email = $contact_email;
    }

    /**
     * @return string
     */
    public function getMemberLevel(): ?string
    {
        return $this->member_level;
    }

    /**
     * @param string $member_level
     * @throws ValidationException
     */
    public function setMemberLevel(string $member_level): void
    {
        if(!in_array($member_level, ICompanyMemberLevel::ValidLevels))
            throw new ValidationException(sprintf("level %s is not valid", $member_level));

        $this->member_level = $member_level;
    }

    /**
     * @return string
     */
    public function getAdminEmail(): ?string
    {
        return $this->admin_email;
    }

    /**
     * @param string $admin_email
     */
    public function setAdminEmail(string $admin_email): void
    {
        $this->admin_email = $admin_email;
    }

    /**
     * @return string
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getOverview(): ?string
    {
        return $this->overview;
    }

    /**
     * @param string $overview
     */
    public function setOverview(string $overview): void
    {
        $this->overview = $overview;
    }

    /**
     * @return string
     */
    public function getCommitment(): ?string
    {
        return $this->commitment;
    }

    /**
     * @param string $commitment
     */
    public function setCommitment(string $commitment): void
    {
        $this->commitment = $commitment;
    }

    /**
     * @return string
     */
    public function getCommitmentAuthor(): ?string
    {
        return $this->commitment_author;
    }

    /**
     * @param string $commitment_author
     */
    public function setCommitmentAuthor(string $commitment_author): void
    {
        $this->commitment_author = $commitment_author;
    }

    /**
     * @return bool
     */
    public function isIsDeleted(): bool
    {
        return $this->is_deleted;
    }

    /**
     * @param bool $is_deleted
     */
    public function setIsDeleted(bool $is_deleted): void
    {
        $this->is_deleted = $is_deleted;
    }


    /**
     * @return array|ProjectSponsorshipType[]
     */
    public function getProjectSponsorships(){
        $res = [];
        foreach ($this->project_sponsorships as $supporting_company){
            $res[] = $supporting_company->getSponsorshipType();
        }
        return $res;
    }

    /**
     * @return ArrayCollection
     */
    public function getSponsorships(){
        return $this->sponsorships;
    }

    /**
     * @return string
     */
    public function getUrlSegment(): ?string{
        return $this->url_segment;
    }

    /**
     * @param string $url_segment
     */
    public function setUrlSegment(string $url_segment): void{
        $this->url_segment = $url_segment;
    }
}
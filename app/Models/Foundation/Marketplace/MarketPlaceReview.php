<?php namespace App\Models\Foundation\Marketplace;
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
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Marketplace\DoctrineReviewRepository")
 * @ORM\Table(name="MarketPlaceReview")
 * Class MarketPlaceReview
 * @package App\Models\Foundation\Marketplace
 */
class MarketPlaceReview extends SilverstripeBaseModel
{

    /**
     * @ORM\Column(name="Title", type="string")
     * @var string
     */
    protected $title;

    /**
     * @ORM\Column(name="Comment", type="string")
     * @var string
     */
    protected $comment;

    /**
     * @ORM\Column(name="Rating", type="float")
     * @var string
     */
    protected $rating;

    /**
     * @ORM\Column(name="Approved", type="boolean")
     * @var bool
     */
    protected $is_approved;

    /**
     * @ORM\ManyToOne(targetEntity="CompanyService",inversedBy="reviews", fetch="LAZY")
     * @ORM\JoinColumn(name="CompanyServiceID", referencedColumnName="ID")
     * @var CompanyService
     */
    protected $company_service;

    /**
     * MarketPlaceReview constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->is_approved = false;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return void
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param float $rating
     * @return void
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
    }

    /**
     * @return bool
     */
    public function isApproved()
    {
        return $this->is_approved;
    }

    /**
     * @param bool $is_approved
     * @return void
     */
    public function setIsApproved(bool $is_approved)
    {
        $this->is_approved = $is_approved;
    }

    /**
     * @return CompanyService
     */
    public function getCompanyService()
    {
        return $this->company_service;
    }

    /**
     * @param CompanyService $company_service
     * @return void
     */
    public function setCompanyService($company_service)
    {
        $this->company_service = $company_service;
    }
}
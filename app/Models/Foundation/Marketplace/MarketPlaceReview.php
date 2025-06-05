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
 * @package App\Models\Foundation\Marketplace
 */
#[ORM\Table(name: 'MarketPlaceReview')]
#[ORM\Entity]
class MarketPlaceReview extends SilverstripeBaseModel
{

    /**
     * @var string
     */
    #[ORM\Column(name: 'Title', type: 'string')]
    protected $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Comment', type: 'string')]
    protected $comment;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Rating', type: 'float')]
    protected $rating;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'Approved', type: 'boolean')]
    protected $is_approved;

    /**
     * @var CompanyService
     */
    #[ORM\JoinColumn(name: 'CompanyServiceID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \CompanyService::class, inversedBy: 'reviews', fetch: 'LAZY')]
    protected $company_service;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @return string
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @return bool
     */
    public function isApproved()
    {
        return $this->is_approved;
    }

    /**
     * @return CompanyService
     */
    public function getCompanyService()
    {
        return $this->company_service;
    }

}
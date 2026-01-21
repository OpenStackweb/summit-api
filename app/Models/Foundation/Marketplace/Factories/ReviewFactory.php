<?php namespace App\Models\Foundation\Marketplace\Factories;

/**
 * Copyright 2026 OpenStack Foundation
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

use App\Models\Foundation\Marketplace\MarketPlaceReview;
use Illuminate\Support\Facades\App;
use models\oauth2\IResourceServerContext;

/**
 * Class ReviewFactory
 * @package App\Models\Foundation\Marketplace\Factories
 */
final class ReviewFactory
{
    /**
     * @param array $data
     * @return MarketPlaceReview
     */
    public static function build(array $data):MarketPlaceReview {
        return self::populate(new MarketPlaceReview, $data);
    }

    /**
     * @param MarketPlaceReview $review
     * @param array $data
     * @return MarketPlaceReview
     */
    public static function populate(MarketPlaceReview $review, array $data):MarketPlaceReview {

        if(isset($data['title']))
            $review->setTitle(trim($data['title']));

        if(isset($data['comment']))
            $review->setComment(trim($data['comment']));

        if(isset($data['rating']))
            $review->setRating(floatval($data['rating']));
        $resource_server_ctx = App::make(IResourceServerContext::class);

        $review->setAuthor($resource_server_ctx->getCurrentUser(false, false));

        return $review;
    }
}
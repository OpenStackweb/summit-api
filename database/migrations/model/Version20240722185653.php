<?php namespace Database\Migrations\Model;
/**
 * Copyright 2024 OpenStack Foundation
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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
/**
 * Class Version20240722185653
 * @package Database\Migrations\Model
 */
final class Version20240722185653 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        /*
         * permissions from IDP
         * 'public_profile_show_photo',
         * 'public_profile_show_fullname',
         * 'public_profile_show_email',
         * 'public_profile_allow_chat_with_me',
         * 'public_profile_show_social_media_info',
         * 'public_profile_show_bio',
         * 'public_profile_show_telephone_number',
         */
        $builder = new Builder($schema);
        if($schema->hasTable("Member") && !$builder->hasColumn("Member", "PublicProfileShowPhoto")) {
            $builder->table('Member', function (Table $table) {
                $table->boolean("PublicProfileShowPhoto")->setNotnull(true)->setDefault(false);
                $table->boolean("PublicProfileShowFullName")->setNotnull(true)->setDefault(true);
                $table->boolean("PublicProfileShowEmail")->setNotnull(true)->setDefault(false);
                $table->boolean("PublicProfileShowSocialMediaInfo")->setNotnull(true)->setDefault(false);
                $table->boolean("PublicProfileShowBio")->setNotnull(true)->setDefault(true);
                $table->boolean("PublicProfileShowTelephoneNumber")->setNotnull(true)->setDefault(false);
                $table->boolean("PublicProfileAllowChatWithMe")->setNotnull(true)->setDefault(false);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("Member") && $builder->hasColumn("Member", "PublicProfileShowPhoto")) {
            $builder->table('Member', function (Table $table) {
                $table->dropColumn("PublicProfileShowPhoto");
                $table->dropColumn("PublicProfileShowFullName");
                $table->dropColumn("PublicProfileShowEmail");
                $table->dropColumn("PublicProfileShowSocialMediaInfo");
                $table->dropColumn("PublicProfileShowBio");
                $table->dropColumn("PublicProfileShowTelephoneNumber");
                $table->dropColumn("PublicProfileAllowChatWithMe");
            });
        }
    }
}

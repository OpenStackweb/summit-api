<?php
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

return
[
    'og_site_name'          => env('SCHEDULE_OG_SITE_NAME', 'OpenStack'),
    'og_image_url'          => env('SCHEDULE_OG_IMAGE_URL', 'https://object-storage-ca-ymq-1.vexxhost.net/swift/v1/6e4619c416ff4bd19e1c087f27a43eea/www-assets-prod/Uploads/newsummitlogo.png'),
    'og_image_secure_url'   => env('SCHEDULE_OG_IMAGE_SECURE_URL', 'https://object-storage-ca-ymq-1.vexxhost.net/swift/v1/6e4619c416ff4bd19e1c087f27a43eea/www-assets-prod/Uploads/newsummitlogo.png'),
    'og_image_width'        => env('SCHEDULE_OG_IMAGE_WIDTH', '240'),
    'og_image_height'       => env('SCHEDULE_OG_IMAGE_HEIGHT', '135'),
    'facebook_app_id'       => env('SCHEDULE_FACEBOOK_APP_ID', '209869746011654'),
    'ios_app_name'          => env('SCHEDULE_IOS_APP_NAME', 'OpenStack Summit'),
    'ios_app_store_id'      => env('SCHEDULE_IOS_APP_STORE_ID', '1071261846'),
    'ios_app_custom_schema' => env('SCHEDULE_IOS_APP_CUSTOM_SCHEMA', 'org.openstack.ios.summit'),
    'android_app_name'      => env('SCHEDULE_ANDROID_APP_NAME', 'OpenStackSummitApplication'),
    'android_app_package'   => env('SCHEDULE_ANDROID_APP_PACKAGE', 'org.openstack.android.summit'),
    'android_custom_schema' => env('SCHEDULE_ANDROID_CUSTOM_SCHEMA', 'org.openstack.android.summit'),
    'twitter_app_name'      => env('SCHEDULE_TWITTER_APP_NAME', '@openstack'),
    'twitter_text'          => env('SCHEDULE_TWITTER_TEXT', 'Check out this %23OpenStack session I\'m attending at the %23OpenStackSummit!'),
];
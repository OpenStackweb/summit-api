<?php
/**
 * Copyright 2018 OpenStack Foundation
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
// public api ( without AUTHZ [OAUTH2.0])
Route::group([
    'namespace' => 'App\Http\Controllers',
    'prefix'     => 'api/public/v1',
    'before'     => [],
    'after'      => [],
    'middleware' => [
        'ssl',
        'rate.limit:1000,1', // 1000 request per minute
        'etags'
    ]
], function(){
    // members
    Route::group(['prefix'=>'members'], function() {
        Route::get('', 'OAuth2MembersApiController@getAll');
    });

    // speakers
    Route::group(['prefix'=>'speakers'], function() {
        Route::group(['prefix'=>'{speaker_id}'], function(){
            Route::group(['prefix'=>'edit-permission'], function(){
                Route::group(['prefix'=>'{token}'], function(){
                    Route::get('approve', 'OAuth2SummitSpeakersApiController@approveSpeakerEditPermission');
                    Route::get('decline', 'OAuth2SummitSpeakersApiController@declineSpeakerEditPermission');
                });
            });
        });
    });

    // summits
    Route::group(['prefix'=>'summits'], function() {
        Route::get('', [ 'middleware' => 'cache:'.Config::get('cache_api_response.get_summit_response_lifetime', 600), 'uses' => 'OAuth2SummitApiController@getSummits']);

        Route::group(['prefix' => 'all'], function () {
            Route::get('current',  'OAuth2SummitApiController@getAllCurrentSummit');
            Route::group(['prefix' => 'selection-plans'], function () {
                Route::get('current/{status}', 'OAuth2SummitSelectionPlansApiController@getCurrentSelectionPlanByStatus')->where('status', 'submission|selection|voting');
            });

            Route::group(['prefix' => 'bookable-rooms'], function () {
                Route::group(['prefix' => 'all'], function () {
                    Route::group(['prefix' => 'reservations'], function () {
                        // api/public/v1/summits/all/bookable-rooms/all/reservations/confirm ( open endpoint for payment gateway callbacks)
                        Route::post("confirm", "OAuth2SummitLocationsApiController@confirmBookableVenueRoomReservation");
                    });
                });
            });
        });

        Route::group(['prefix' => '{id}'], function () {
            Route::get('', [ 'middleware' => 'cache:'.Config::get('cache_api_response.get_summit_response_lifetime', 1200), 'uses' => 'OAuth2SummitApiController@getSummit'])->where('id', 'current|[0-9]+');
            Route::get('published-events', 'OAuth2SummitEventsApiController@getScheduledEvents');
            // locations
            Route::group(['prefix' => 'locations'], function () {
                Route::group(['prefix' => '{location_id}'], function () {
                    Route::get('', 'OAuth2SummitLocationsApiController@getLocation');
                    Route::get('/events/published','OAuth2SummitLocationsApiController@getLocationPublishedEvents');
                    Route::group(['prefix' => 'banners'], function () {
                        Route::get('', 'OAuth2SummitLocationsApiController@getLocationBanners');
                    });
                });
            });

            // notifications
            Route::group(['prefix' => 'notifications'], function () {
                Route::get('sent', 'OAuth2SummitNotificationsApiController@getAllApprovedByUser');
            });

        });
    });

    // marketplace
    Route::group(array('prefix' => 'marketplace'), function () {

        Route::group(array('prefix' => 'appliances'), function () {
            Route::get('', 'AppliancesApiController@getAll');
        });

        Route::group(array('prefix' => 'distros'), function () {
            Route::get('', 'DistributionsApiController@getAll');
        });

        Route::group(array('prefix' => 'consultants'), function () {
            Route::get('', 'ConsultantsApiController@getAll');
        });

        Route::group(array('prefix' => 'hosted-private-clouds'), function () {
            Route::get('', 'PrivateCloudsApiController@getAll');
        });

        Route::group(array('prefix' => 'remotely-managed-private-clouds'), function () {
            Route::get('', 'RemoteCloudsApiController@getAll');
        });

        Route::group(array('prefix' => 'public-clouds'), function () {
            Route::get('', 'PublicCloudsApiController@getAll');
        });
    });

    // countries
    Route::group(array('prefix' => 'countries'), function () {
        Route::get('', 'CountriesApiController@getAll');
    });

    // languages
    Route::group(array('prefix' => 'languages'), function () {
        Route::get('', 'LanguagesApiController@getAll');
    });

});

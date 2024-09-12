<?php
/**
 * Copyright 2021 OpenStack Foundation
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

use Illuminate\Support\Facades\Route;

//OAuth2 Protected API V2


// summits
Route::group(['prefix' => 'summits'], function () {

    Route::group(['prefix' => '{id}'], function () {

        Route::get('', ['uses' => 'OAuth2SummitApiController@getSummit'])->where('id', 'current|[0-9]+');
        // events
        Route::group(['prefix' => 'events'], function () {

            Route::group(['prefix' => '{event_id}'], function () {
                Route::group(['prefix' => 'feedback'], function () {
                    Route::post('', 'OAuth2SummitEventsApiController@addMyEventFeedbackReturnId');
                    Route::put('', 'OAuth2SummitEventsApiController@updateMyEventFeedbackReturnId');
                });
            });
        });
        // presentations
        Route::group(['prefix' => 'presentations'], function () {
            Route::group(['prefix' => 'voteable'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@getAllVoteablePresentationsV2']);
                Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@getAllVoteablePresentationsV2CSV']);
            });
        });
        // ticket types
        Route::group(['prefix' => 'ticket-types'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsTicketTypesApiController@getAllBySummitV2']);
        });

        // attendees
        Route::group(['prefix' => 'attendees'], function () {
            Route::group(['prefix' => '{attendee_id}'], function () {
                Route::get('', ['middleware' => 'auth.user,cache:1200,ATTENDEE,attendee_id' , 'uses' => 'OAuth2SummitsTicketTypesApiController@getAttendeeExtraQuestionsV2']);
            });
        });

    });
});

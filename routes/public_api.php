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


Route::group(['prefix' => 'sponsored-projects'], function(){
    Route::get('', 'OAuth2SponsoredProjectApiController@getAll');
    Route::group(['prefix'=>'{id}'], function(){
        Route::get('',  [ 'uses' => 'OAuth2SponsoredProjectApiController@get']);
        Route::group(['prefix'=>'sponsorship-types'], function(){
            Route::get('',[ 'middleware' => 'cache:1800', 'uses' => 'OAuth2SponsoredProjectApiController@getAllSponsorshipTypes']);
            Route::group(['prefix'=>'{sponsorship_type_id}'], function(){
                Route::get('',  [ 'uses' => 'OAuth2SponsoredProjectApiController@getSponsorshipType']);
                Route::group(['prefix'=>'supporting-companies'], function(){
                    Route::get('',  [ 'uses' => 'OAuth2SponsoredProjectApiController@getSupportingCompanies']);
                });
            });
        });
    });
});

// elections
Route::group(['prefix' => 'elections'], function(){
    Route::group(['prefix'=>'current'], function(){
        Route::get('',  [ 'uses' => 'OAuth2ElectionsApiController@getCurrent']);
        Route::group(['prefix'=>'candidates'], function(){
            Route::get('',  [ 'uses' => 'OAuth2ElectionsApiController@getCurrentCandidates']);
            Route::group(['prefix'=>'gold'], function(){
                Route::get('',  [ 'uses' => 'OAuth2ElectionsApiController@getCurrentGoldCandidates']);
            });
        });
    });
});

// files
Route::group(['prefix' => 'files'], function () {
    Route::post('upload', 'OAuth2ChunkedFilesApiController@uploadFile');
});

// members
Route::group(['prefix' => 'members'], function () {
    Route::get('', 'OAuth2MembersApiController@getAll');
    Route::group(['prefix' => '{member_id}'], function () {
        Route::get('', 'OAuth2MembersApiController@getById');
    });
});

// members
Route::group(['prefix' => 'legal-documents'], function () {
    Route::get('{id}', 'OAuth2LegalDocumentsApiController@getById');
});

// speakers
Route::group(['prefix' => 'speakers'], function () {
    Route::group(['prefix' => '{speaker_id}'], function () {
        Route::group(['prefix' => 'edit-permission'], function () {
            Route::group(['prefix' => '{token}'], function () {
                Route::get('approve', 'OAuth2SummitSpeakersApiController@approveSpeakerEditPermission');
                Route::get('decline', 'OAuth2SummitSpeakersApiController@declineSpeakerEditPermission');
            });
        });
    });
});

// summits
Route::group(['prefix' => 'summits'], function () {
    Route::get('', ['middleware' => 'cache:' . Config::get('cache_api_response.get_summit_response_lifetime', 600), 'uses' => 'OAuth2SummitApiController@getSummits']);

    Route::group(['prefix' => 'all'], function () {

        Route::get('', 'OAuth2SummitApiController@getAllSummits');
        Route::get('current', 'OAuth2SummitApiController@getAllCurrentSummit');
        Route::get('{id}', 'OAuth2SummitApiController@getAllSummitByIdOrSlug');

        Route::group(['prefix' => 'payments'], function () {
            Route::group(['prefix' => '{application_name}'], function () {
                Route::post('confirm', 'PaymentGatewayWebHookController@genericConfirm');
            });
        });

        Route::group(['prefix' => 'orders'], function () {
            Route::group(['prefix' => '{order_hash}'], function () {
                Route::group(['prefix' => 'tickets'], function () {
                    Route::put('', "OAuth2SummitOrdersApiController@updateTicketsByOrderHash");
                });
            });

            Route::group(['prefix' => 'all'], function () {
                Route::group(['prefix' => 'tickets'], function () {
                    Route::group(['prefix' => '{hash}'], function () {
                        Route::get('', "OAuth2SummitOrdersApiController@getTicketByHash");
                        Route::put('', "OAuth2SummitOrdersApiController@updateTicketByHash");
                        Route::put('regenerate', "OAuth2SummitOrdersApiController@regenerateTicketHash");
                        Route::get('pdf', "OAuth2SummitOrdersApiController@getTicketPDFByHash");
                    });
                });
            });
        });
    });

    Route::group(['prefix' => '{id}'], function () {

        Route::group(['prefix' => 'payments'], function () {
            Route::group(['prefix' => '{application_name}'], function () {
                Route::post('confirm', 'PaymentGatewayWebHookController@confirm');
            });
        });

        Route::group(['prefix' => 'selection-plans'], function () {
            Route::get('current/{status}', 'OAuth2SummitSelectionPlansApiController@getCurrentSelectionPlanByStatus')->where('status', 'submission|selection|voting');
        });

        Route::get('', ['middleware' => 'cache:' . Config::get('cache_api_response.get_summit_response_lifetime', 1200), 'uses' => 'OAuth2SummitApiController@getSummit'])->where('id', 'current|[0-9]+');
        // members
        Route::group(['prefix' => 'members'], function () {
            Route::group(['prefix' => 'all'], function () {
                Route::group(['prefix' => 'schedule'], function () {
                    Route::group(['prefix' => 'ics'], function () {
                        Route::get('{cid}', 'OAuth2SummitMembersApiController@getCalendarFeedICS');
                    });
                });
            });
        });
        // events
        Route::group(['prefix' => 'events'], function () {
            Route::group(['prefix' => 'published'], function () {
                Route::get('', ['middleware' => 'cache:1200', 'uses' => 'OAuth2SummitEventsApiController@getScheduledEvents']);
            });

            Route::group(array('prefix' => '{event_id}'), function () {
                Route::group(['prefix' => 'published'], function () {
                    Route::get('', ['middleware' => 'cache:' . Config::get('cache_api_response.get_published_event_response_lifetime', 300), 'uses' => 'OAuth2SummitEventsApiController@getScheduledEvent']);
                });
            });

            Route::group(['prefix' => 'all'], function () {
                Route::group(['prefix' => 'published'], function () {
                    Route::get('tags', 'OAuth2SummitEventsApiController@getScheduledEventsTags');
                });
            });
        });
        // locations
        Route::group(['prefix' => 'locations'], function () {
            Route::group(['prefix' => '{location_id}'], function () {
                Route::get('', 'OAuth2SummitLocationsApiController@getLocation');
                Route::get('/events/published', 'OAuth2SummitLocationsApiController@getLocationPublishedEvents');
                Route::group(['prefix' => 'banners'], function () {
                    Route::get('', 'OAuth2SummitLocationsApiController@getLocationBanners');
                });
            });
        });
        // notifications
        Route::group(['prefix' => 'notifications'], function () {
            Route::get('sent', 'OAuth2SummitNotificationsApiController@getAllApprovedByUser');
        });
        // speakers
        Route::group(['prefix' => 'speakers'], function () {
            Route::get('', 'OAuth2SummitSpeakersApiController@getSpeakers');
            Route::group(['prefix' => '{speaker_id}'], function () {
                Route::get('', 'OAuth2SummitSpeakersApiController@getSummitSpeaker')->where('speaker_id', '[0-9]+');
            });
        });
        // orders
        Route::group(['prefix' => 'orders'], function () {
            Route::post('reserve', 'OAuth2SummitOrdersApiController@reserve');
            Route::group(['prefix' => '{hash}'], function () {
                Route::put('checkout', 'OAuth2SummitOrdersApiController@checkout');
                Route::group(['prefix' => 'tickets'], function () {
                    Route::get('mine', 'OAuth2SummitOrdersApiController@getMyTicketByOrderHash');
                });
                Route::delete('', 'OAuth2SummitOrdersApiController@cancel');
            });
        });

        // order-extra-questions

        Route::group(['prefix' => 'order-extra-questions'], function () {
            Route::get('', ['uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@getAllBySummit']);
        });

        // taxes types -- only dev

        if(\Illuminate\Support\Facades\App::environment("dev")){
            Route::group(['prefix' => 'tax-types'], function () {
                Route::get('', ['uses' => 'OAuth2SummitTaxTypeApiController@getAllBySummit']);
                Route::post('', ['uses' => 'OAuth2SummitTaxTypeApiController@add']);
                Route::group(['prefix' => '{tax_id}'], function () {
                    Route::get('', [ 'uses' => 'OAuth2SummitTaxTypeApiController@get']);
                    Route::put('', [ 'uses' => 'OAuth2SummitTaxTypeApiController@update']);
                    Route::delete('', ['uses' => 'OAuth2SummitTaxTypeApiController@delete']);
                });
            });

            // ticket types
            Route::group(['prefix' => 'ticket-types'], function () {
                Route::get('', 'OAuth2SummitsTicketTypesApiController@getAllBySummit');
            });
        }
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

// timezones
Route::group(array('prefix' => 'timezones'), function () {
    Route::get('', 'TimezonesApiController@getAll');
});

// releases
Route::group(array('prefix' => 'releases'), function () {
    Route::group(array('prefix' => 'current'), function () {
        Route::get('', 'ReleasesApiController@getCurrent');
    });
});
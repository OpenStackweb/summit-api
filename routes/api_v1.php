<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use libs\utils\CacheRegions;

//OAuth2 Protected API

// audit log
Route::group(['prefix' => 'audit-logs'], function () {
    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2AuditLogController@getAll']);
});

// members
Route::group(['prefix' => 'members'], function () {
    Route::get('', 'OAuth2MembersApiController@getAll');

    Route::group(['prefix' => 'me'], function () {
        // get my member info
        Route::get('', 'OAuth2MembersApiController@getMyMember');
        Route::put('', 'OAuth2MembersApiController@updateMyMember');

        // my affiliations
        Route::group(['prefix' => 'affiliations'], function () {
            Route::get('', ['uses' => 'OAuth2MembersApiController@getMyMemberAffiliations']);
            Route::post('', ['uses' => 'OAuth2MembersApiController@addMyAffiliation']);
            Route::group(['prefix' => '{affiliation_id}'], function () {
                Route::put('', ['uses' => 'OAuth2MembersApiController@updateMyAffiliation']);
                Route::delete('', ['uses' => 'OAuth2MembersApiController@deleteMyAffiliation']);
            });
        });

        Route::group(['prefix' => 'membership'], function () {
            Route::put('foundation', ['uses' => 'OAuth2MembersApiController@signFoundationMembership']);
            Route::put('community', ['uses' => 'OAuth2MembersApiController@signCommunityMembership']);
            Route::put('individual', ['uses' => 'OAuth2MembersApiController@signIndividualMembership']);
            Route::delete('resign', ['uses' => 'OAuth2MembersApiController@resignMembership']);
        });
    });

    Route::group(['prefix' => '{member_id}'], function () {

        Route::group(['prefix' => 'affiliations'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2MembersApiController@getMemberAffiliations']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2MembersApiController@addAffiliation']);
            Route::group(['prefix' => '{affiliation_id}'], function () {
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2MembersApiController@updateAffiliation']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2MembersApiController@deleteAffiliation']);
            });
        });

        Route::group(array('prefix' => 'rsvp'), function () {
            Route::group(['prefix' => '{rsvp_id}'], function () {
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2MembersApiController@deleteRSVP']);
            });
        });
    });
});

// tags
Route::group(['prefix' => 'tags'], function () {
    Route::get('', 'OAuth2TagsApiController@getAll');
    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2TagsApiController@addTag']);
    Route::group(['prefix' => '{id}'], function () {
        Route::get('', 'OAuth2TagsApiController@getTag');
        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2TagsApiController@updateTag']);
        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2TagsApiController@deleteTag']);
    });
});

// companies

Route::group(['prefix' => 'companies'], function () {
    Route::get('', 'OAuth2CompaniesApiController@getAllCompanies');
    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2CompaniesApiController@add']);
    Route::group(['prefix' => '{id}'], function () {
        Route::get('', ['uses' => 'OAuth2CompaniesApiController@get']);
        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2CompaniesApiController@update']);
        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2CompaniesApiController@delete']);
        Route::group(['prefix' => 'logo'], function () {
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2CompaniesApiController@addCompanyLogo']);
            Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2CompaniesApiController@deleteCompanyLogo']);
            Route::group(['prefix' => 'big'], function () {
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2CompaniesApiController@addCompanyBigLogo']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2CompaniesApiController@deleteCompanyBigLogo']);
            });
        });
    });
});

// sponsored projects
Route::group(['prefix' => 'sponsored-projects'], function () {
    Route::get('', 'OAuth2SponsoredProjectApiController@getAll');
    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@add']);

    Route::group(['prefix' => '{id}'], function () {

        Route::get('', ['uses' => 'OAuth2SponsoredProjectApiController@get']);
        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@update']);
        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@delete']);

        Route::group(['prefix' => 'sponsorship-types'], function () {
            Route::get('', 'OAuth2SponsoredProjectApiController@getAllSponsorshipTypes');
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@addSponsorshipType']);
            Route::group(['prefix' => '{sponsorship_type_id}'], function () {

                Route::get('', ['uses' => 'OAuth2SponsoredProjectApiController@getSponsorshipType']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@updateSponsorshipType']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@deleteSponsorshipType']);

                Route::group(['prefix' => 'supporting-companies'], function () {
                    Route::get('', ['uses' => 'OAuth2SponsoredProjectApiController@getSupportingCompanies']);
                    Route::post('', ['uses' => 'OAuth2SponsoredProjectApiController@addSupportingCompanies']);
                    Route::group(['prefix' => '{company_id}'], function () {
                        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@getSupportingCompany']);
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@updateSupportingCompanies']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@deleteSupportingCompanies']);
                    });
                });
            });
        });

        Route::group(['prefix' => 'logo'], function () {
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@addSponsoredProjectLogo']);
            Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@deleteSponsoredProjectLogo']);
        });

        Route::group(['prefix' => 'subprojects'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsoredProjectApiController@getSubprojects']);
        });
    });
});

// organizations
Route::group(['prefix' => 'organizations'], function () {
    Route::get('', 'OAuth2OrganizationsApiController@getAll');
    Route::post('', 'OAuth2OrganizationsApiController@add');
});

// groups
Route::group(['prefix' => 'groups'], function () {
    Route::get('', 'OAuth2GroupsApiController@getAll');
});

// summit-media-file-types
Route::group(['prefix' => 'summit-media-file-types'], function () {
    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaFileTypeApiController@getAll']);
    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaFileTypeApiController@add']);
    Route::group(['prefix' => '{id}'], function () {
        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaFileTypeApiController@get']);
        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaFileTypeApiController@update']);
        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaFileTypeApiController@delete']);
    });
});

// summits
Route::group(array('prefix' => 'summits'), function () {

    Route::get('', ['uses' => 'OAuth2SummitApiController@getSummits']);

    Route::group(['prefix' => 'all'], function () {

        Route::get('', 'OAuth2SummitApiController@getAllSummits');
        Route::group(['prefix' => '{id}'], function () {
            Route::get('', 'OAuth2SummitApiController@getAllSummitByIdOrSlug');
            Route::group(['prefix' => 'registration-stats'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@getAllSummitByIdOrSlugRegistrationStats']);
                Route::get('check-ins', ['middleware' => 'auth.user', 'uses' =>'OAuth2SummitApiController@getAttendeesCheckinsOverTimeStats']);
                Route::get('purchased-tickets', ['middleware' => 'auth.user', 'uses' =>'OAuth2SummitApiController@getPurchasedTicketsOverTimeStats']);
            });
        });

        Route::group(['prefix' => 'locations'], function () {
            // GET /api/v1/summits/all/locations/bookable-rooms/all/reservations/{id}
            Route::get('bookable-rooms/all/reservations/{id}', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@getReservationById']);
        });

        Route::group(['prefix' => 'registration-invitations'], function () {
            Route::group(['prefix' => '{token}'], function () {
                Route::get('', ['uses' => 'OAuth2SummitRegistrationInvitationApiController@getInvitationByToken']);
            });
        });

        Route::group(['prefix' => 'orders'], function () {
            Route::get('me', 'OAuth2SummitOrdersApiController@getAllMyOrders');

            Route::group(['prefix' => 'all'], function () {
                Route::group(['prefix' => 'tickets'], function () {
                    Route::group(['prefix' => '{ticket_id}'], function () {
                        Route::put('', 'OAuth2SummitOrdersApiController@updateMyTicketById');
                        Route::get('pdf', 'OAuth2SummitOrdersApiController@getMyTicketPDFById');
                    });

                    Route::group(['prefix' => 'me'], function () {
                        Route::get('', 'OAuth2SummitTicketApiController@getAllMyTickets');
                    });
                });
            });

            Route::group(['prefix' => '{order_id}'], function () {
                Route::group(['prefix' => 'refund'], function () {
                    Route::delete('', 'OAuth2SummitOrdersApiController@requestRefundMyOrder');
                });
                Route::put('resend', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@reSendOrderEmail']);
                Route::get('', 'OAuth2SummitOrdersApiController@getMyOrderById');
                Route::put('', 'OAuth2SummitOrdersApiController@updateMyOrder');
                Route::group(['prefix' => 'tickets'], function () {
                    Route::get('', 'OAuth2SummitOrdersApiController@getMyTicketsByOrderId');
                    Route::group(['prefix' => '{ticket_id}'], function () {
                        Route::get('', 'OAuth2SummitOrdersApiController@getMyTicketById');

                        Route::get('pdf', 'OAuth2SummitOrdersApiController@getTicketPDFByOrderId');
                        Route::group(['prefix' => 'refund'], function () {
                            Route::delete('', 'OAuth2SummitOrdersApiController@requestRefundMyTicket');
                            Route::delete('cancel', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@cancelRefundRequestTicket']);
                        });

                        Route::group(['prefix' => 'attendee'], function () {
                            Route::put('', 'OAuth2SummitOrdersApiController@assignAttendee');
                            Route::put('reinvite', 'OAuth2SummitOrdersApiController@reInviteAttendee');
                            Route::delete('', 'OAuth2SummitOrdersApiController@removeAttendee');
                        });
                    });
                });
            });

        });
    });

    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@addSummit']);

    Route::group(['prefix' => '{id}'], function () {

        Route::group(['prefix' => 'signs'], function () {
            Route::get('', 'OAuth2SummitSignApiController@getAllBySummit');
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSignApiController@add']);
            Route::group(['prefix' => '{sign_id}'], function () {
                Route::get('', 'OAuth2SummitSignApiController@get');
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSignApiController@update']);
            });
        });

        Route::group(['prefix' => 'metrics'], function () {
            Route::put('enter', 'OAuth2SummitMetricsApiController@enter');
            Route::post('leave', 'OAuth2SummitMetricsApiController@leave');
            // on site
            Route::group(['prefix' => 'onsite'], function () {
                Route::get('enter', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMetricsApiController@checkOnSiteEnter']);
                Route::put('enter', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMetricsApiController@onSiteEnter']);
                Route::post('leave', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMetricsApiController@onSiteLeave']);
            });
        });

        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@updateSummit']);
        Route::group(['prefix' => 'logo'], function () {
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@addSummitLogo']);
            Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@deleteSummitLogo']);
            Route::post('secondary', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@addSummitSecondaryLogo']);
            Route::delete('secondary', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@deleteSummitSecondaryLogo']);
        });

        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@deleteSummit']);
        Route::get('', ['middleware' =>
            sprintf('cache:%s,%s,id',
                Config::get('cache_api_response.get_summit_response_lifetime', 1200),
                CacheRegions::CacheRegionSummits,
            ),
            'uses' => 'OAuth2SummitApiController@getSummit'])->where('id', 'current|[0-9]+');

        // selection plan extra questions ( by summit )

        Route::group(['prefix' => 'selection-plan-extra-questions'], function () {
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@addExtraQuestion']);
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@getExtraQuestions']);
            Route::get('metadata', ['uses' => 'OAuth2SummitSelectionPlansApiController@getExtraQuestionsMetadata']);
            Route::group(['prefix' => '{question_id}'], function () {

                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@deleteExtraQuestion']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@updateExtraQuestion']);
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@getExtraQuestion']);

                Route::group(['prefix' => 'values'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@addExtraQuestionValue']);
                    Route::group(['prefix' => '{value_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@updateExtraQuestionValue']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@deleteExtraQuestionValue']);
                    });
                });
            });
        });

        // selection plans

        Route::group(['prefix' => 'selection-plans'], function () {
            Route::get('', ['uses' => 'OAuth2SummitSelectionPlansApiController@getAll']);
            Route::get('me', 'OAuth2SummitSelectionPlansApiController@getMySelectionPlans');
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@addSelectionPlan']);
            Route::group(['prefix' => '{selection_plan_id}'], function () {

                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@updateSelectionPlan']);
                Route::get('', ['uses' => 'OAuth2SummitSelectionPlansApiController@getSelectionPlan']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@deleteSelectionPlan']);

                Route::group(['prefix' => 'track-groups'], function () {
                    Route::put('{track_group_id}', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@addTrackGroupToSelectionPlan']);
                    Route::delete('{track_group_id}', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@deleteTrackGroupToSelectionPlan']);
                });

                // extra questions ( by selection plan)

                Route::group(['prefix' => 'extra-questions'], function () {
                    Route::get('', ['uses' => 'OAuth2SummitSelectionPlansApiController@getExtraQuestionsBySelectionPlan']);
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@addExtraQuestionAndAssign']);
                    Route::get('metadata', ['uses' => 'OAuth2SummitSelectionPlansApiController@getExtraQuestionsMetadataBySelectionPlan']);

                    Route::group(['prefix' => '{question_id}'], function () {

                        Route::get('', ['uses' => 'OAuth2SummitSelectionPlansApiController@getExtraQuestionBySelectionPlan']);
                        Route::put('', ['uses' => 'OAuth2SummitSelectionPlansApiController@updateExtraQuestionBySelectionPlan']);
                        Route::post('', ['uses' => 'OAuth2SummitSelectionPlansApiController@assignExtraQuestion']);
                        // un-assign question from selection plan
                        Route::delete('', ['uses' => 'OAuth2SummitSelectionPlansApiController@removeExtraQuestion']);

                        Route::group(['prefix' => 'values'], function () {
                            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@addExtraQuestionValueBySelectionPlan']);
                            Route::group(['prefix' => '{value_id}'], function () {
                                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@updateExtraQuestionValueBySelectionPlan']);
                                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@deleteExtraQuestionValueBySelectionPlan']);
                            });
                        });
                    });
                });

                // event types

                Route::group(['prefix' => 'event-types'], function () {
                    Route::group(['prefix' => '{event_type_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@attachEventType']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@detachEventType']);
                    });
                });

                // allowed members

                Route::group(['prefix' => 'allowed-members'], function () {
                    Route::get('', ['uses' => 'OAuth2SummitSelectionPlansApiController@getAllowedMembers']);
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@addAllowedMember']);
                    Route::group(['prefix' => '{allowed_member_id}'], function () {
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@removeAllowedMember']);
                    });
                    Route::post('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@importAllowedMembers']);
                });

                // presentations

                Route::group(['prefix' => 'presentations'], function () {
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@getSelectionPlanPresentations']);
                    Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@getSelectionPlanPresentationsCSV']);
                    Route::group(['prefix' => 'all'], function () {
                        // category-change-requests
                        Route::group(['prefix' => 'category-change-requests'], function () {
                            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@getAllPresentationCategoryChangeRequest']);
                        });
                    });

                    Route::group(['prefix' => '{presentation_id}'], function () {

                        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@getSelectionPlanPresentation']);
                        Route::put('view', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@markPresentationAsViewed']);
                        Route::group(['prefix' => 'comments'], function () {
                            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@addCommentToPresentation']);
                        });

                        // category-change-requests
                        Route::group(['prefix' => 'category-change-requests'], function () {
                            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@createPresentationCategoryChangeRequest']);
                            Route::group(['prefix' => '{category_change_request_id}'], function () {
                                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@resolvePresentationCategoryChangeRequest']);
                            });
                        });

                        // presentation actions

                        Route::group(['prefix' => 'actions'], function () {
                            Route::group(['prefix' => '{action_type_id}'], function () {
                                Route::put('complete', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPresentationActionApiController@complete']);
                                Route::delete('incomplete', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPresentationActionApiController@uncomplete']);
                            });
                        });

                        // track chair rating

                        Route::group(['prefix' => 'track-chair-scores'], function () {
                            Route::post('{score_type_id}', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@addTrackChairScore']);
                            Route::delete('{score_type_id}', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@removeTrackChairScore']);
                        });
                    });
                });

                // allowed presentation action types

                Route::group(['prefix' => 'allowed-presentation-action-types'], function () {
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@getAllowedPresentationActionTypes']);
                    Route::group(['prefix' => '{type_id}'], function () {
                        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@getAllowedPresentationActionType']);
                        Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@addAllowedPresentationActionType']);
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@updateAllowedPresentationActionType']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSelectionPlansApiController@removeAllowedPresentationActionType']);
                    });
                });

                // selection lists ( track chairs )

                Route::group(['prefix' => 'tracks'], function () {
                    Route::group(['prefix' => '{track_id}'], function () {
                        Route::group(['prefix' => 'selection-lists'], function () {

                            Route::group(['prefix' => 'team'], function () {
                                Route::get('', [
                                    'middleware' => 'auth.user',
                                    'uses' => 'OAuth2SummitSelectedPresentationListApiController@getTeamSelectionList'
                                ]);

                                Route::post('', [
                                    'middleware' => 'auth.user',
                                    'uses' => 'OAuth2SummitSelectedPresentationListApiController@createTeamSelectionList'
                                ]);
                            });

                            Route::group(['prefix' => 'individual'], function () {
                                Route::group(['prefix' => 'owner'], function () {

                                    Route::group(['prefix' => 'me'], function () {
                                        Route::post('', [
                                            'middleware' => 'auth.user',
                                            'uses' => 'OAuth2SummitSelectedPresentationListApiController@createIndividualSelectionList'
                                        ]);
                                    });

                                    Route::group(['prefix' => '{owner_id}'], function () {
                                        Route::get('', [
                                            'middleware' => 'auth.user',
                                            'uses' => 'OAuth2SummitSelectedPresentationListApiController@getIndividualSelectionList'
                                        ]);
                                    });

                                });
                            });

                            Route::group(['prefix' => 'individual'], function () {

                                Route::group(['prefix' => 'presentation-selections'], function () {

                                    Route::group(['prefix' => '{collection}'], function () {

                                        Route::group(['prefix' => 'presentations'], function () {

                                            Route::group(['prefix' => '{presentation_id}'], function () {

                                                Route::post('', [
                                                    'middleware' => 'auth.user',
                                                    'uses' => 'OAuth2SummitSelectedPresentationListApiController@assignPresentationToMyIndividualList'
                                                ]);

                                                Route::delete('', [
                                                    'middleware' => 'auth.user',
                                                    'uses' => 'OAuth2SummitSelectedPresentationListApiController@removePresentationFromMyIndividualList'
                                                ]);

                                            });

                                        });

                                    });

                                });

                            });

                            Route::group(['prefix' => '{list_id}'], function () {
                                Route::put('reorder', [
                                    'middleware' => 'auth.user',
                                    'uses' => 'OAuth2SummitSelectedPresentationListApiController@reorderSelectionList'
                                ]);
                            });
                        });
                    });
                });

                // track chair rating types and score types crud

                Route::group(['prefix' => 'track-chair-rating-types'], function () {

                    Route::get('', ['uses' => 'OAuth2SummitTrackChairRatingTypesApiController@getTrackChairRatingTypes']);

                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTrackChairRatingTypesApiController@addTrackChairRatingType']);

                    Route::group(['prefix' => '{type_id}'], function () {

                        Route::get('', ['uses' => 'OAuth2SummitTrackChairRatingTypesApiController@getTrackChairRatingType']);

                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTrackChairRatingTypesApiController@updateTrackChairRatingType']);

                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTrackChairRatingTypesApiController@deleteTrackChairRatingType']);

                        Route::group(['prefix' => 'score-types'], function () {

                            Route::get('', ['uses' => 'OAuth2SummitTrackChairScoreTypesApiController@getTrackChairScoreTypes']);

                            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTrackChairScoreTypesApiController@addTrackChairScoreType']);

                            Route::group(['prefix' => '{score_type_id}'], function () {

                                Route::get('', ['uses' => 'OAuth2SummitTrackChairScoreTypesApiController@getTrackChairScoreType']);

                                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTrackChairScoreTypesApiController@updateTrackChairScoreType']);

                                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTrackChairScoreTypesApiController@deleteTrackChairScoreType']);
                            });
                        });
                    });
                });
            });
        });

        // RSVP templates
        Route::group(['prefix' => 'rsvp-templates'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@getAllBySummit']);

            Route::group(['prefix' => 'questions'], function () {
                Route::get('metadata', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@getRSVPTemplateQuestionsMetadata']);
            });

            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@addRSVPTemplate']);
            Route::group(['prefix' => '{template_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@getRSVPTemplate']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@updateRSVPTemplate']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@deleteRSVPTemplate']);
                Route::group(['prefix' => 'questions'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@addRSVPTemplateQuestion']);
                    Route::group(['prefix' => '{question_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@updateRSVPTemplateQuestion']);
                        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@getRSVPTemplateQuestion']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@deleteRSVPTemplateQuestion']);
                        // multi values questions
                        Route::group(['prefix' => 'values'], function () {
                            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@addRSVPTemplateQuestionValue']);
                            Route::group(['prefix' => '{value_id}'], function () {
                                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@getRSVPTemplateQuestionValue']);
                                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@updateRSVPTemplateQuestionValue']);
                                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRSVPTemplatesApiController@deleteRSVPTemplateQuestionValue']);
                            });
                        });
                    });
                });
            });
        });

        // notifications
        Route::group(['prefix' => 'notifications'], function () {
            Route::get('sent', 'OAuth2SummitNotificationsApiController@getAllApprovedByUser');
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitNotificationsApiController@getAll']);
            Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitNotificationsApiController@getAllCSV']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitNotificationsApiController@addPushNotification']);
            Route::group(['prefix' => '{notification_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitNotificationsApiController@getById']);
                Route::put('approve', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitNotificationsApiController@approveNotification']);
                Route::delete('approve', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitNotificationsApiController@unApproveNotification']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitNotificationsApiController@deleteNotification']);
            });
        });

        // submitters
        Route::group(['prefix' => 'submitters'], function () {

            Route::get('', 'OAuth2SummitSubmittersApiController@getAllBySummit');
            Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSubmittersApiController@getAllBySummitCSV']);
            Route::group(['prefix' => 'all'], function () {
                Route::put('send', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSubmittersApiController@send']);
            });
        });

        // speakers
        Route::group(['prefix' => 'speakers'], function () {

            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersApiController@addSpeakerBySummit']);
            Route::get('', 'OAuth2SummitSpeakersApiController@getSpeakers');
            Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersApiController@getSpeakersCSV']);
            Route::get('on-schedule', 'OAuth2SummitSpeakersApiController@getSpeakersOnSchedule');
            Route::get('me', 'OAuth2SummitSpeakersApiController@getMySummitSpeaker');
            Route::group(['prefix' => 'all'], function () {
                Route::put('send', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersApiController@send']);
            });
            Route::group(['prefix' => '{speaker_id}'], function () {
                Route::get('', ['middleware' =>
                    sprintf('cache:%s,%s,speaker_id',
                        3600,
                        CacheRegions::CacheRegionSpeakers,
                    ),
                    'uses' => 'OAuth2SummitSpeakersApiController@getSummitSpeaker'])->where('speaker_id', 'me|[0-9]+');
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersApiController@updateSpeakerBySummit'])->where('speaker_id', 'me|[0-9]+');
            });
        });

        // speakers assistance
        Route::group(['prefix' => 'speakers-assistances'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersAssistanceApiController@getBySummit']);
            Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersAssistanceApiController@getBySummitCSV']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersAssistanceApiController@addSpeakerSummitAssistance']);
            Route::group(['prefix' => '{assistance_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersAssistanceApiController@getSpeakerSummitAssistanceBySummit']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersAssistanceApiController@deleteSpeakerSummitAssistance']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersAssistanceApiController@updateSpeakerSummitAssistance']);
                // @deprecated
                // Route::post('mail', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersAssistanceApiController@sendSpeakerSummitAssistanceAnnouncementMail']);
            });
        });

        // events
        Route::group(array('prefix' => 'events'), function () {

            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@getEvents']);

            Route::group(['prefix' => 'csv'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@getEventsCSV']);
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@importEventData']);
            });
            // bulk actions
            Route::delete('/publish', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@unPublishEvents']);
            Route::put('/publish', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@updateAndPublishEvents']);
            Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@updateEvents']);

            Route::group(array('prefix' => 'unpublished'), function () {
                Route::get('', 'OAuth2SummitEventsApiController@getUnpublishedEvents');
                //Route::get('{event_id}', 'OAuth2SummitEventsApiController@getUnpublisedEvent');
            });

            Route::group(array('prefix' => 'published'), function () {
                Route::get('', 'OAuth2SummitEventsApiController@getScheduledEvents');
                Route::get('/empty-spots', 'OAuth2SummitEventsApiController@getScheduleEmptySpots');
            });

            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@addEvent']);
            Route::group(['prefix' => '{event_id}'], function () {

                Route::post('/clone', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@cloneEvent']);
                Route::get('', 'OAuth2SummitEventsApiController@getEvent');

                Route::group(['prefix' => 'published'], function () {
                    Route::get('', ['middleware' =>
                        sprintf('cache:%s,%s,event_id',
                            Config::get('cache_api_response.get_published_event_response_lifetime', 600),
                            CacheRegions::CacheRegionEvents,
                        ),
                        'uses' => 'OAuth2SummitEventsApiController@getScheduledEvent']);
                    Route::get('tokens',  'OAuth2SummitEventsApiController@getScheduledEventJWT');
                    Route::get('streaming-info', 'OAuth2SummitEventsApiController@getScheduledEventStreamingInfo');
                    Route::post('mail', 'OAuth2SummitEventsApiController@shareScheduledEventByEmail');
                    // media uploads
                    Route::group(['prefix' => 'media-uploads'], function () {
                        Route::get('', 'OAuth2PresentationApiController@getPresentationMediaUploads');
                    });
                });

                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@updateEvent']);
                Route::put('live-info', ['uses' => 'OAuth2SummitEventsApiController@updateEventLiveInfo']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@deleteEvent']);
                Route::put('/publish', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@publishEvent']);
                Route::delete('/publish', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@unPublishEvent']);

                Route::post('/attachment', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@addEventAttachment']);

                Route::group(['prefix' => 'feedback'], function () {
                    Route::get('', 'OAuth2SummitEventsApiController@getEventFeedback');
                    Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@getEventFeedbackCSV']);
                    Route::group(['prefix' => '{feedback_id}'], function () {
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@deleteEventFeedback']);
                    });
                });

                Route::group(['prefix' => 'image'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@addEventImage']);
                    Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@deleteEventImage']);
                });

                Route::group(['prefix' => 'type'], function () {
                    Route::group(['prefix' => '{type_id}'], function () {
                        Route::put('upgrade', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@upgradeEvent']);
                    });
                });

                Route::group(['prefix' => 'overflow'], function () {
                    Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@setOverflow']);
                    Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEventsApiController@clearOverflow']);
                });
            });
        });

        // schedule settings
        Route::group(array('prefix' => 'schedule-settings'), function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitScheduleSettingsApiController@getAllBySummit']);
            Route::get('metadata', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitScheduleSettingsApiController@getMetadata']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitScheduleSettingsApiController@add']);
            Route::post('seed', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitScheduleSettingsApiController@seedDefaults']);
            Route::group(['prefix' => '{config_id}'], function () {
                Route::group(['prefix' => 'filters'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitScheduleSettingsApiController@addFilter']);
                    Route::group(['prefix' => '{filter_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitScheduleSettingsApiController@updateFilter']);
                    });
                });
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitScheduleSettingsApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitScheduleSettingsApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitScheduleSettingsApiController@delete']);
            });
        });

        // presentations
        Route::group(['prefix' => 'presentations'], function () {
            Route::get('', ['uses' => 'OAuth2SummitEventsApiController@getAllPresentations']);
            Route::group(['prefix' => 'voteable'], function () {
                Route::get('', ['uses' => 'OAuth2SummitEventsApiController@getAllVoteablePresentations']);
                Route::group(['prefix' => '{presentation_id}'], function () {
                    Route::get('', ['uses' => 'OAuth2SummitEventsApiController@getVoteablePresentation']);
                });
            });

            // opened without role CFP - valid selection plan on CFP status
            Route::post('', 'OAuth2PresentationApiController@submitPresentation');
            // import from mux
            Route::post('all/import/mux', 'OAuth2PresentationApiController@importAssetsFromMUX');

            Route::group(['prefix' => '{presentation_id}'], function () {

                // opened without role CFP - valid selection plan on CFP status
                Route::get('', 'OAuth2PresentationApiController@getPresentationSubmission');
                Route::put('', 'OAuth2PresentationApiController@updatePresentationSubmission');

                Route::put('completed', 'OAuth2PresentationApiController@completePresentationSubmission');

                Route::delete('', 'OAuth2PresentationApiController@deletePresentation');

                // videos
                Route::group(['prefix' => 'videos'], function () {
                    Route::get('', 'OAuth2PresentationApiController@getPresentationVideos');
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@addVideo']);
                    Route::group(['prefix' => '{video_id}'], function () {
                        Route::get('', 'OAuth2PresentationApiController@getPresentationVideo');
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@updateVideo']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@deleteVideo']);
                    });
                });

                // extra-questions
                Route::group(['prefix' => 'extra-questions'], function () {
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@getPresentationsExtraQuestions']);
                });

                // slides
                Route::group(['prefix' => 'slides'], function () {
                    Route::get('', 'OAuth2PresentationApiController@getPresentationSlides');
                    Route::post('', 'OAuth2PresentationApiController@addPresentationSlide');
                    Route::group(['prefix' => '{slide_id}'], function () {
                        Route::get('', 'OAuth2PresentationApiController@getPresentationSlide');
                        Route::put('', 'OAuth2PresentationApiController@updatePresentationSlide');
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@deletePresentationSlide']);
                    });
                });

                // links
                Route::group(['prefix' => 'links'], function () {
                    Route::get('', 'OAuth2PresentationApiController@getPresentationLinks');
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@addPresentationLink']);
                    Route::group(['prefix' => '{link_id}'], function () {
                        Route::get('', 'OAuth2PresentationApiController@getPresentationLink');
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@updatePresentationLink']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@deletePresentationLink']);
                    });
                });

                // media uploads
                Route::group(['prefix' => 'media-uploads'], function () {
                    Route::get('', 'OAuth2PresentationApiController@getPresentationMediaUploads');
                    Route::post('', 'OAuth2PresentationApiController@addPresentationMediaUpload');
                    Route::group(['prefix' => '{media_upload_id}'], function () {
                        Route::get('', 'OAuth2PresentationApiController@getPresentationMediaUpload');
                        Route::put('', 'OAuth2PresentationApiController@updatePresentationMediaUpload');
                        Route::delete('', 'OAuth2PresentationApiController@deletePresentationMediaUpload');
                    });
                });

                // attendees votes
                Route::group(['prefix' => 'attendee-votes'], function () {
                    Route::get('', ['uses' => 'OAuth2PresentationApiController@getAttendeeVotes']);
                    Route::post('', ['uses' => 'OAuth2PresentationApiController@castAttendeeVote']);
                    Route::delete('', ['uses' => 'OAuth2PresentationApiController@unCastAttendeeVote']);
                });

                Route::group(['prefix' => 'comments'], function () {
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@getComments']);
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@addComment']);
                    Route::group(['prefix' => '{comment_id}'], function () {
                        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@getComment']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@deleteComment']);
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@updateComment']);
                    });
                });

                // speakers

                Route::group(['prefix' => 'speakers'], function () {
                    Route::group(['prefix' => '{speaker_id}'], function () {
                        Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@addSpeaker2Presentation']);
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@updateSpeakerInPresentation']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationApiController@removeSpeakerFromPresentation']);
                    });
                });
            });
        });

        // locations
        Route::group(['prefix' => 'locations'], function () {

            Route::get('', 'OAuth2SummitLocationsApiController@getLocations');
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addLocation']);

            Route::get('metadata', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@getMetadata']);

            Route::group(['prefix' => 'bookable-rooms'], function () {
                // GET /api/v1/summits/{id}/locations/bookable-rooms
                Route::get('', 'OAuth2SummitLocationsApiController@getBookableVenueRooms');

                Route::group(['prefix' => 'all'], function () {
                    Route::group(['prefix' => 'reservations'], function () {
                        // GET /api/v1/summits/{id}/locations/bookable-rooms/all/reservations
                        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@getAllReservationsBySummit']);
                        // GET /api/v1/summits/{id}/locations/bookable-rooms/all/reservations/csv
                        Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@getAllReservationsBySummitCSV']);
                        // GET /api/v1/summits/{id}/locations/bookable-rooms/all/reservations/me
                        Route::get('me', 'OAuth2SummitLocationsApiController@getMyBookableVenueRoomReservations');
                        Route::group(['prefix' => '{reservation_id}'], function () {
                            // DELETE /api/v1/summits/{id}/locations/bookable-rooms/all/reservations/{reservation_id}
                            Route::delete('', 'OAuth2SummitLocationsApiController@cancelMyBookableVenueRoomReservation');
                        });
                    });
                });

                Route::group(['prefix' => '{room_id}'], function () {
                    // GET /api/v1/summits/{id}/locations/bookable-rooms/{room_id}
                    Route::get('', 'OAuth2SummitLocationsApiController@getBookableVenueRoom');
                    // GET /api/v1/summits/{id}/locations/bookable-rooms/{room_id}/availability/{day}
                    Route::get('availability/{day}', 'OAuth2SummitLocationsApiController@getBookableVenueRoomAvailability');

                    Route::group(['prefix' => 'reservations'], function () {
                        // POST /api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations
                        Route::post('', 'OAuth2SummitLocationsApiController@createBookableVenueRoomReservation');
                        // POST /api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations/offline
                        Route::post('offline',  ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@createOfflineBookableVenueRoomReservation']);
                        Route::group(['prefix' => '{reservation_id}'], function () {
                            // PUT /api/v1/summits/{id}/bookable-rooms/{room_id}/reservations/{reservation_id}
                            Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateBookableVenueRoomReservation']);
                            // GET /api/v1/summits/{id}/bookable-rooms/{room_id}/reservations/{reservation_id}
                            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@getBookableVenueRoomReservation']);
                            // DELETE /api/v1/summits/{id}/bookable-rooms/{room_id}/reservations/{reservation_id}/refund
                            Route::delete('refund', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@refundBookableVenueRoomReservation']);
                            // DELETE /api/v1/summits/{id}/bookable-rooms/{room_id}/reservations/{reservation_id}/cancel

                            Route::delete('cancel', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@cancelBookableVenueRoomReservation']);

                        });
                    });

                });
            });

            // venues

            Route::group(['prefix' => 'venues'], function () {

                Route::get('', 'OAuth2SummitLocationsApiController@getVenues');
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addVenue']);
                Route::group(['prefix' => 'all'], function () {
                    Route::group(['prefix' => 'rooms'], function () {
                        Route::get('', 'OAuth2SummitLocationsApiController@getAllVenuesRooms');
                    });
                    Route::group(['prefix' => 'bookable-rooms'], function () {
                        Route::get('', 'OAuth2SummitLocationsApiController@getBookableVenueRooms');
                    });
                });
                Route::group(['prefix' => '{venue_id}'], function () {
                    Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateVenue']);

                    Route::group(['prefix' => 'rooms'], function () {
                        Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addVenueRoom']);
                        Route::group(['prefix' => '{room_id}'], function () {
                            Route::get('', 'OAuth2SummitLocationsApiController@getVenueRoom');
                            Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateVenueRoom']);
                            Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@deleteVenueRoom']);
                            Route::group(['prefix' => 'image'], function () {
                                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addVenueRoomImage']);
                                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@removeVenueRoomImage']);
                            });
                        });
                    });

                    // bookable-rooms
                    Route::group(['prefix' => 'bookable-rooms'], function () {
                        // POST /api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms
                        Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addVenueBookableRoom']);
                        Route::group(['prefix' => '{room_id}'], function () {
                            // GET /api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}
                            Route::get('', 'OAuth2SummitLocationsApiController@getBookableVenueRoomByVenue');
                            // PUT /api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}
                            Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateVenueBookableRoom']);
                            // DELETE /api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}
                            Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@deleteVenueBookableRoom']);
                            // attributes

                            Route::group(['prefix' => 'attributes'], function () {
                                Route::group(['prefix' => '{attribute_id}'], function () {
                                    // PUT /api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}/attributes/{attribute_id}
                                    Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addVenueBookableRoomAttribute']);
                                    // DELETE /api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}/attributes/{attribute_id}
                                    Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@deleteVenueBookableRoomAttribute']);
                                });

                            });
                        });
                    });

                    Route::group(['prefix' => 'floors'], function () {
                        Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addVenueFloor']);
                        Route::group(['prefix' => '{floor_id}'], function () {
                            Route::get('', 'OAuth2SummitLocationsApiController@getVenueFloor');
                            Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateVenueFloor']);
                            Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@deleteVenueFloor']);
                            Route::group(['prefix' => 'image'], function () {
                                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addVenueFloorImage']);
                                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@removeVenueFloorImage']);
                            });
                            Route::group(['prefix' => 'rooms'], function () {
                                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addVenueFloorRoom']);
                                Route::group(['prefix' => '{room_id}'], function () {
                                    Route::get('', 'OAuth2SummitLocationsApiController@getVenueFloorRoom');
                                    Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateVenueFloorRoom']);
                                });
                            });
                            Route::group(['prefix' => 'bookable-rooms'], function () {
                                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addVenueFloorBookableRoom']);
                                Route::group(['prefix' => '{room_id}'], function () {
                                    Route::get('', 'OAuth2SummitLocationsApiController@getVenueFloorBookableRoom');
                                    Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateVenueFloorBookableRoom']);
                                });
                            });
                        });
                    });
                });
            });

            Route::group(['prefix' => 'airports'], function () {
                Route::get('', 'OAuth2SummitLocationsApiController@getAirports');
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addAirport']);
                Route::group(['prefix' => '{airport_id}'], function () {
                    Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateAirport']);
                });
            });

            Route::group(['prefix' => 'hotels'], function () {
                Route::get('', 'OAuth2SummitLocationsApiController@getHotels');
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addExternalLocation']);
                Route::group(['prefix' => '{hotel_id}'], function () {
                    Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateExternalLocation']);
                });
            });

            Route::group(['prefix' => 'external-locations'], function () {
                Route::get('', 'OAuth2SummitLocationsApiController@getExternalLocations');
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addHotel']);
                Route::group(['prefix' => '{external_location_id}'], function () {
                    Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateHotel']);
                });
            });

            Route::group(['prefix' => '{location_id}'], function () {
                Route::get('', 'OAuth2SummitLocationsApiController@getLocation');

                // locations maps
                Route::group(['prefix' => 'maps'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addLocationMap']);
                    Route::group(['prefix' => '{map_id}'], function () {
                        Route::get('', 'OAuth2SummitLocationsApiController@getLocationMap');
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateLocationMap']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@deleteLocationMap']);
                    });
                });

                // locations images
                Route::group(['prefix' => 'images'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addLocationImage']);
                    Route::group(['prefix' => '{image_id}'], function () {
                        Route::get('', 'OAuth2SummitLocationsApiController@getLocationImage');
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateLocationImage']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@deleteLocationImage']);
                    });
                });

                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateLocation']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@deleteLocation']);
                Route::get('/events/published', 'OAuth2SummitLocationsApiController@getLocationPublishedEvents')->where('location_id', 'tbd|[0-9]+');
                Route::get('/events', 'OAuth2SummitLocationsApiController@getLocationEvents')->where('location_id', 'tbd|[0-9]+');
                // location banners
                Route::group(['prefix' => 'banners'], function () {
                    Route::get('', 'OAuth2SummitLocationsApiController@getLocationBanners');
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@addLocationBanner']);
                    Route::group(['prefix' => '{banner_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@updateLocationBanner']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@deleteLocationBanner']);
                    });
                });
            });

            Route::group(['prefix' => 'copy'], function () {
                Route::post('{target_summit_id}', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitLocationsApiController@copy']);
            });
        });

        // bookable rooms attributes
        Route::group(['prefix' => 'bookable-room-attribute-types'], function () {
            Route::get('', 'OAuth2SummitBookableRoomsAttributeTypeApiController@getAllBookableRoomAttributeTypes');
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBookableRoomsAttributeTypeApiController@addBookableRoomAttributeType']);
            Route::group(['prefix' => '{type_id}'], function () {
                Route::get('', 'OAuth2SummitBookableRoomsAttributeTypeApiController@getBookableRoomAttributeType');
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBookableRoomsAttributeTypeApiController@updateBookableRoomAttributeType']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBookableRoomsAttributeTypeApiController@deleteBookableRoomAttributeType']);
                Route::group(['prefix' => 'values'], function () {
                    Route::get('', 'OAuth2SummitBookableRoomsAttributeTypeApiController@getAllBookableRoomAttributeValues');
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBookableRoomsAttributeTypeApiController@addBookableRoomAttributeValue']);
                    Route::group(['prefix' => '{value_id}'], function () {
                        Route::get('', 'OAuth2SummitBookableRoomsAttributeTypeApiController@getBookableRoomAttributeValue');
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBookableRoomsAttributeTypeApiController@updateBookableRoomAttributeValue']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBookableRoomsAttributeTypeApiController@deleteBookableRoomAttributeValue']);
                    });
                });
            });
        });

        // event types
        Route::group(['prefix' => 'event-types'], function () {
            Route::get('', 'OAuth2SummitsEventTypesApiController@getAllBySummit');
            Route::get('csv', 'OAuth2SummitsEventTypesApiController@getAllBySummitCSV');
            Route::post('seed-defaults', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsEventTypesApiController@seedDefaultEventTypesBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsEventTypesApiController@addEventTypeBySummit']);
            Route::group(['prefix' => '{event_type_id}'], function () {
                Route::get('', 'OAuth2SummitsEventTypesApiController@getEventTypeBySummit');
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsEventTypesApiController@updateEventTypeBySummit']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsEventTypesApiController@deleteEventTypeBySummit']);

                Route::group(['prefix' => 'summit-documents'], function () {
                    Route::group(['prefix' => '{document_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsEventTypesApiController@addSummitDocument']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsEventTypesApiController@removeSummitDocument']);
                    });
                });
            });
        });

        // documents
        Route::group(['prefix' => 'summit-documents'], function () {
            Route::get('', 'OAuth2SummitDocumentsApiController@getAllBySummit');
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitDocumentsApiController@add']);
            Route::group(['prefix' => '{document_id}', 'where' => ['document_id' => '[0-9]+']], function () {
                Route::get('', 'OAuth2SummitDocumentsApiController@get');
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitDocumentsApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitDocumentsApiController@delete']);

                Route::group(['prefix' => 'file'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitDocumentsApiController@addFile']);
                    Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitDocumentsApiController@removeFile']);
                });

                Route::group(['prefix' => 'event-types'], function () {
                    Route::group(['prefix' => '{event_type_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitDocumentsApiController@addEventType']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitDocumentsApiController@removeEventType']);
                    });
                });
            });
        });

        // ticket types
        Route::group(['prefix' => 'ticket-types'], function () {
            Route::get('', 'OAuth2SummitsTicketTypesApiController@getAllBySummit');
            Route::get('csv', 'OAuth2SummitsTicketTypesApiController@getAllBySummitCSV');
            Route::get('allowed', 'OAuth2SummitsTicketTypesApiController@getAllowedBySummitAndCurrentMember');
            Route::post('seed-defaults', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsTicketTypesApiController@seedDefaultTicketTypesBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsTicketTypesApiController@addTicketTypeBySummit']);
            Route::group(['prefix' => '{ticket_type_id}'], function () {
                Route::get('', 'OAuth2SummitsTicketTypesApiController@getTicketTypeBySummit');
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsTicketTypesApiController@updateTicketTypeBySummit']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsTicketTypesApiController@deleteTicketTypeBySummit']);
            });
            Route::group(['prefix' => 'all'], function () {
                Route::group(['prefix' => 'currency'], function () {
                    Route::put('{currency_symbol}', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitsTicketTypesApiController@updateCurrencySymbol']);
                });
            });
        });

        // begin registration endpoints

        // tax-types
        Route::group(['prefix' => 'tax-types'], function () {
            Route::get('', ['uses' => 'OAuth2SummitTaxTypeApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTaxTypeApiController@add']);
            Route::group(['prefix' => '{tax_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTaxTypeApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTaxTypeApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTaxTypeApiController@delete']);

                Route::group(['prefix' => 'ticket-types'], function () {
                    Route::group(['prefix' => '{ticket_type_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTaxTypeApiController@addTaxToTicketType']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTaxTypeApiController@removeTaxFromTicketType']);
                    });
                });
            });
        });

        // payment-gateway-profiles
        Route::group(['prefix' => 'payment-gateway-profiles'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PaymentGatewayProfileApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PaymentGatewayProfileApiController@add']);
            Route::group(['prefix' => '{payment_profile_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PaymentGatewayProfileApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PaymentGatewayProfileApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PaymentGatewayProfileApiController@delete']);
            });
        });

        // refund-policies
        Route::group(['prefix' => 'refund-policies'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRefundPolicyTypeApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRefundPolicyTypeApiController@add']);
            Route::group(['prefix' => '{policy_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRefundPolicyTypeApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRefundPolicyTypeApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRefundPolicyTypeApiController@delete']);
            });
        });

        // sponsorships-types
        Route::group(['prefix' => 'sponsorships-types'], function () {

            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipTypeApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipTypeApiController@add']);
            Route::group(['prefix' => '{type_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipTypeApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipTypeApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipTypeApiController@delete']);

                Route::group(['prefix' => 'badge-image'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipTypeApiController@addBadgeImage']);
                    Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipTypeApiController@removeBadgeImage']);
                });
            });
        });

        // sponsors
        Route::group(['prefix' => 'sponsors'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@add']);
            Route::group(['prefix' => 'all'], function () {
                Route::group(['prefix' => 'extra-questions'], function () {
                    Route::get('metadata', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@getMetadata']);
                });

                Route::group(['prefix' => 'promo-codes'], function () {
                    Route::group(['prefix' => 'all'], function () {
                        Route::put('send', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@sendSponsorPromoCodes']);
                    });
                });
            });
            Route::group(['prefix' => '{sponsor_id}'], function () {

                // side image
                Route::group(['prefix' => 'side-image'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addSponsorSideImage']);
                    Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@deleteSponsorSideImage']);
                });

                // header image
                Route::group(['prefix' => 'header-image'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addSponsorHeaderImage']);
                    Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@deleteSponsorHeaderImage']);
                    Route::group(['prefix' => 'mobile'], function () {
                        // mobile
                        Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addSponsorHeaderImageMobile']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@deleteSponsorHeaderImageMobile']);
                    });
                });

                // carousel image
                Route::group(['prefix' => 'carousel-advertise-image'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addSponsorCarouselAdvertiseImage']);
                    Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@deleteSponsorCarouselAdvertiseImage']);
                });

                Route::group(['prefix' => 'user-info-grants'], function () {
                    Route::post('me', ['uses' => 'OAuth2SummitBadgeScanApiController@addGrant']);
                });

                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@delete']);
                Route::group(['prefix' => 'users'], function () {
                    Route::group(['prefix' => '{member_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addSponsorUser']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@removeSponsorUser']);
                    });
                });
                // ads
                Route::group(['prefix' => 'ads'], function () {
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@getAds']);
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addAd']);
                    Route::group(['prefix' => '{ad_id}'], function () {
                        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@getAd']);
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@updateAd']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@deleteAd']);
                        Route::group(['prefix' => 'image'], function () {
                            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addAdImage']);
                            Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@removeAdImage']);
                        });
                    });
                });
                // materials
                Route::group(['prefix' => 'materials'], function () {
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@getMaterials']);
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addMaterial']);
                    Route::group(['prefix' => '{material_id}'], function () {
                        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@getMaterial']);
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@updateMaterial']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@deleteMaterial']);
                    });
                });
                // social networks
                Route::group(['prefix' => 'social-networks'], function () {
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@getSocialNetworks']);
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addSocialNetwork']);
                    Route::group(['prefix' => '{social_network_id}'], function () {
                        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@getSocialNetwork']);
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@updateSocialNetwork']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@deleteSocialNetwork']);
                    });
                });

                // extra questions
                Route::group(['prefix' => 'extra-questions'], function () {
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@getExtraQuestions']);
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addExtraQuestion']);
                    Route::group(['prefix' => '{extra_question_id}'], function () {
                        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@getExtraQuestion']);
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@updateExtraQuestion']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@deleteExtraQuestion']);
                        // multi values
                        Route::group(['prefix' => 'values'], function () {
                            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addExtraQuestionValue']);
                            Route::group(['prefix' => '{value_id}'], function () {
                                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@updateExtraQuestionValue']);
                                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@deleteExtraQuestionValue']);
                            });
                        });
                    });
                });

                // lead report settings
                Route::group(['prefix' => 'lead-report-settings'], function(){
                    Route::get('metadata', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@getLeadReportSettingsMetadata']);
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorApiController@addLeadReportSettings']);
                    Route::put('', ['uses' => 'OAuth2SummitSponsorApiController@updateLeadReportSettings']);
                });

                // sponsorships
                Route::group(['prefix' => 'sponsorships'], function(){
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipsApiController@getAll']);
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipsApiController@addFromTypes']);
                    Route::group(['prefix' => '{sponsorship_id}'], function () {
                        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipsApiController@getById']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipsApiController@remove']);

                        //Add-ons
                        Route::group(['prefix' => 'add-ons'], function(){
                            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipsApiController@getAllAddOns']);
                            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipsApiController@addNewAddOn']);
                            Route::group(['prefix' => '{add_on_id}'], function(){
                                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipsApiController@getAddOnById']);
                                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipsApiController@updateAddOn']);
                                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipsApiController@removeAddOn']);
                            });
                        });
                    });
                });
            });
        });

        // order-extra-questions
        Route::group(['prefix' => 'order-extra-questions'], function () {
            Route::get('metadata', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@getMetadata']);
            Route::get('', ['uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@add']);
            Route::post('seed-defaults', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@seedDefaultSummitExtraOrderQuestionTypesBySummit']);
            Route::group(['prefix' => '{question_id}'], function () {
                Route::get('', ['uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@delete']);

                // values
                Route::group(['prefix' => 'values'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@addQuestionValue']);
                    Route::group(['prefix' => '{value_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@updateQuestionValue']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@deleteQuestionValue']);
                    });
                });

                // sub questions
                Route::group(['prefix' => 'sub-question-rules'], function () {
                    Route::get('', ['uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@getSubQuestionRules']);
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@addSubQuestionRule']);
                    Route::group(['prefix' => '{rule_id}'], function () {
                        Route::get('', ['uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@getSubQuestionRule']);
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@updateSubQuestionRule']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@deleteSubQuestionRule']);
                    });
                });
            });
        });

        // access-levels
        Route::group(['prefix' => 'access-level-types'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAccessLevelTypeApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAccessLevelTypeApiController@add']);
            Route::group(['prefix' => '{level_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAccessLevelTypeApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAccessLevelTypeApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAccessLevelTypeApiController@delete']);
            });
        });

        // badge-feature-types
        Route::group(['prefix' => 'badge-feature-types'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeFeatureTypeApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeFeatureTypeApiController@add']);
            Route::group(['prefix' => '{feature_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeFeatureTypeApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeFeatureTypeApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeFeatureTypeApiController@delete']);
                Route::group(['prefix' => 'image'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeFeatureTypeApiController@addFeatureImage']);
                    Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeFeatureTypeApiController@deleteFeatureImage']);
                });
            });
        });

        // badge-scans
        Route::group(['prefix' => 'badge-scans'], function () {
            Route::get('me', 'OAuth2SummitBadgeScanApiController@getAllMyBadgeScans');
            Route::get('',['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeScanApiController@getAllBySummit']);
            Route::get('csv', ['middleware' => 'auth.user', 'uses' =>'OAuth2SummitBadgeScanApiController@getAllBySummitCSV']);
            Route::post('', ['middleware' => 'auth.user', 'uses' =>"OAuth2SummitBadgeScanApiController@add"]);
            Route::put('checkin', "OAuth2SummitBadgeScanApiController@checkIn");
            Route::group(['prefix' => '{scan_id}'], function () {
                Route::put('', ['middleware' => 'auth.user', 'uses' =>"OAuth2SummitBadgeScanApiController@update"]);
                Route::get('', ['middleware' => 'auth.user', 'uses' =>"OAuth2SummitBadgeScanApiController@get"]);
            });
        });

        // badge-types

        Route::group(['prefix' => 'badge-types'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeTypeApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeTypeApiController@add']);
            Route::group(['prefix' => '{badge_type_id}'], function () {

                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeTypeApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeTypeApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeTypeApiController@delete']);

                Route::group(['prefix' => 'access-levels'], function () {
                    Route::group(['prefix' => '{access_level_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeTypeApiController@addAccessLevelToBadgeType']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeTypeApiController@removeAccessLevelFromBadgeType']);
                    });
                });

                Route::group(['prefix' => 'features'], function () {
                    Route::group(['prefix' => '{feature_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeTypeApiController@addFeatureToBadgeType']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeTypeApiController@removeFeatureFromBadgeType']);
                    });
                });

                Route::group(['prefix' => 'view-types'], function () {
                    Route::group(['prefix' => '{badge_view_type_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeTypeApiController@addViewTypeToBadgeType']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeTypeApiController@removeViewTypeFromBadgeType']);
                    });
                });

            });
        });

        // badge-view-types

        Route::group(['prefix' => 'badge-view-types'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeViewTypeApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeViewTypeApiController@add']);
            Route::group(['prefix' => '{badge_view_type_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeViewTypeApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeViewTypeApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgeViewTypeApiController@delete']);
            });
        });

        // orders
        Route::group(['prefix' => 'orders'], function () {
            Route::get('me', 'OAuth2SummitOrdersApiController@getAllMyOrdersBySummit');
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@getAllBySummit']);
            Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@getAllBySummitCSV']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@add']);

            Route::group(['prefix' => 'all'], function () {
                Route::group(['prefix' => 'tickets'], function () {
                    Route::group(['prefix' => 'me'], function () {
                        Route::get('', 'OAuth2SummitTicketApiController@getAllMyTicketsBySummit');
                    });
                });
            });

            Route::group(['prefix' => '{order_id}', 'where' => [
                'order_id' => '[0-9]+'
            ]], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@delete']);

                Route::group(['prefix' => 'tickets'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@addTicket']);
                    Route::group(['prefix' => 'all'], function () {
                        Route::group(['prefix' => 'refund-requests'], function () {
                            Route::get('approved', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@getAllRefundApprovedRequests']);
                        });

                    });
                    Route::group(['prefix' => '{ticket_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@updateTicket']);
                        Route::group(['prefix' => 'activate'], function () {
                            Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@activateTicket']);
                            Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@deActivateTicket']);
                        });
                        Route::get('pdf', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitOrdersApiController@getTicketPDFBySummit']);
                        Route::put('delegate',  'OAuth2SummitOrdersApiController@delegateTicket');
                    });
                });
            });
            Route::post('reserve', 'OAuth2SummitOrdersApiController@reserve');
            Route::group(['prefix' => '{hash}', 'where' => [
                'hash' => '[a-zA-Z0-9]+'
            ]], function () {
                Route::put('checkout', 'OAuth2SummitOrdersApiController@checkout');
                Route::group(['prefix' => 'tickets'], function () {
                    Route::get('mine', 'OAuth2SummitOrdersApiController@getMyTicketByOrderHash');
                });
                Route::delete('', 'OAuth2SummitOrdersApiController@cancel');
            });
        });

        // tickets
        Route::group(['prefix' => 'tickets'], function () {

            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@getAllBySummit']);
            Route::get('external', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@getAllBySummitExternal']);

            Route::group(['prefix' => 'csv'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@getAllBySummitCSV']);
                Route::get('template', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@getImportTicketDataTemplate']);
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@importTicketData']);
            });

            Route::post('ingest', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@ingestExternalTicketData']);

            Route::group(['prefix' => '{ticket_id}'], function () {

                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@get']);
                // badge endpoints
                Route::group(['prefix' => 'badge'], function () {

                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@createAttendeeBadge']);
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@getAttendeeBadge']);

                    Route::group(['prefix' => 'current'], function () {
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@deleteAttendeeBadge']);


                        // printing endpoints

                        Route::group(['prefix' => 'prints'], function () {
                            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeeBadgePrintApiController@getAllBySummitAndTicket']);
                            Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeeBadgePrintApiController@deleteBadgePrints']);
                            Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeeBadgePrintApiController@getAllBySummitAndTicketCSV']);
                        });

                        // legacy ( default )

                        Route::group(['prefix' => 'print'], function () {
                            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@canPrintAttendeeBadgeDefault']);
                            Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@printAttendeeBadgeDefault']);
                        });

                        // view type
                        Route::group(['prefix' => '{view_type}'], function () {
                            Route::group(['prefix' => 'print'], function () {
                                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@canPrintAttendeeBadge']);
                                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@printAttendeeBadge']);
                            });
                        });

                        Route::group(['prefix' => 'features'], function () {
                            Route::group(['prefix' => '{feature_id}'], function () {
                                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@addAttendeeBadgeFeature']);
                                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@removeAttendeeBadgeFeature']);
                            });
                        });
                        Route::group(['prefix' => 'type'], function () {
                            Route::group(['prefix' => '{type_id}'], function () {
                                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@updateAttendeeBadgeType']);
                            });
                        });
                    });
                });
                // badge endpoints
                Route::delete('refund', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTicketApiController@refundTicket']);
            });
        });

        // attendees
        Route::group(array('prefix' => 'attendees'), function () {

            Route::group(['prefix' => 'all'], function () {
                Route::group(['prefix' => 'notes'], function () {
                    Route::get('', 'OAuth2SummitAttendeeNotesApiController@getAllAttendeeNotes');
                    Route::get('csv', 'OAuth2SummitAttendeeNotesApiController@getAllAttendeeNotesCSV');
                });
                Route::put('send', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeesApiController@send']);
            });

            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeesApiController@getAttendeesBySummit']);
            Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeesApiController@getAttendeesBySummitCSV']);
            Route::group(['prefix' => 'me'], function () {
                Route::get('', 'OAuth2SummitAttendeesApiController@getOwnAttendee');
                Route::group(['prefix' => 'allowed-extra-questions'], function () {
                    Route::get('', 'OAuth2SummitOrderExtraQuestionTypeApiController@getOwnAttendeeAllowedExtraQuestions');
                });
            });

            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeesApiController@addAttendee']);

            Route::group(array('prefix' => '{attendee_id}'), function () {
                Route::get('me', [ 'uses' => 'OAuth2SummitAttendeesApiController@getMyRelatedAttendee']);
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeesApiController@getAttendee']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeesApiController@updateAttendee']);
                Route::put('virtual-check-in', ['uses' => 'OAuth2SummitAttendeesApiController@doVirtualCheckin']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeesApiController@deleteAttendee']);
                // attendee schedule
                Route::group(array('prefix' => 'schedule'), function () {
                    Route::get('', 'OAuth2SummitAttendeesApiController@getAttendeeSchedule')->where('attendee_id', 'me');

                    Route::group(array('prefix' => '{event_id}'), function () {
                        Route::post('', 'OAuth2SummitAttendeesApiController@addEventToAttendeeSchedule')->where('attendee_id', 'me|[0-9]+');
                        Route::delete('', 'OAuth2SummitAttendeesApiController@removeEventFromAttendeeSchedule')->where('attendee_id', 'me|[0-9]+');
                        Route::delete('/rsvp', 'OAuth2SummitAttendeesApiController@deleteEventRSVP')->where('attendee_id', 'me|[0-9]+');
                        Route::put('/check-in', 'OAuth2SummitAttendeesApiController@checkingAttendeeOnEvent')->where('attendee_id', 'me|[0-9]+');
                    });
                });

                // attendee tickets
                Route::group(array('prefix' => 'tickets'), function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeesApiController@addAttendeeTicket']);
                    Route::group(array('prefix' => '{ticket_id}'), function () {
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeesApiController@deleteAttendeeTicket']);
                        Route::group(array('prefix' => 'reassign'), function () {
                            Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeesApiController@reassignAttendeeTicket']);
                            Route::put('{other_member_id}', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeesApiController@reassignAttendeeTicketByMember']);
                        });
                    });
                });

                // allowed extra questions
                Route::group(array('prefix' => 'allowed-extra-questions'), function () {
                    Route::get('', [ 'uses' => 'OAuth2SummitOrderExtraQuestionTypeApiController@getAttendeeExtraQuestions']);
                });

                // attendee notes
                Route::group(['prefix' => 'notes', 'where' => ['note_id' => '[0-9]+']], function () {
                    Route::get('', 'OAuth2SummitAttendeeNotesApiController@getAttendeeNotes');
                    Route::get('csv', 'OAuth2SummitAttendeeNotesApiController@getAttendeeNotesCSV');
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeeNotesApiController@addAttendeeNote']);
                    Route::group(array('prefix' => '{note_id}'), function () {
                        Route::get('', 'OAuth2SummitAttendeeNotesApiController@getAttendeeNote');
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeeNotesApiController@updateAttendeeNote']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAttendeeNotesApiController@deleteAttendeeNote']);
                    });
                });
            });
        });

        // registration invitations
        Route::group(array('prefix' => 'registration-invitations'), function () {

            Route::get('me', ['uses' => 'OAuth2SummitRegistrationInvitationApiController@getMyInvitation']);

            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationInvitationApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationInvitationApiController@add']);
            Route::group(['prefix' => 'csv'], function () {
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationInvitationApiController@ingestInvitations']);
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationInvitationApiController@getAllBySummitCSV']);
            });

            Route::group(['prefix' => 'all'], function () {
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationInvitationApiController@deleteAll']);
                Route::put('send', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationInvitationApiController@send']);
            });

            Route::group(['prefix' => '{invitation_id}'], function () {
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationInvitationApiController@update'])->where('invitation_id', '[0-9]+');
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationInvitationApiController@get'])->where('invitation_id', '[0-9]+');
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationInvitationApiController@delete'])->where('invitation_id', '[0-9]+');
            });
        });

        // submission invitations
        Route::group(array('prefix' => 'submission-invitations'), function () {


            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSubmissionInvitationApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSubmissionInvitationApiController@add']);
            Route::group(['prefix' => 'csv'], function () {
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSubmissionInvitationApiController@ingestInvitations']);
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSubmissionInvitationApiController@getAllBySummitCSV']);
            });

            Route::group(['prefix' => 'all'], function () {
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSubmissionInvitationApiController@deleteAll']);
                Route::put('send', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSubmissionInvitationApiController@send']);
            });

            Route::group(['prefix' => '{invitation_id}'], function () {
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSubmissionInvitationApiController@update'])->where('invitation_id', '[0-9]+');
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSubmissionInvitationApiController@get'])->where('invitation_id', '[0-9]+');
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSubmissionInvitationApiController@delete'])->where('invitation_id', '[0-9]+');
            });
        });

        // badges
        Route::group(['prefix' => 'badges'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgesApiController@getAllBySummit']);
            Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitBadgesApiController@getAllBySummitCSV']);
        });

        // external orders @todo to deprecate
        Route::group(['prefix' => 'external-orders'], function () {
            Route::get('{external_order_id}', 'OAuth2SummitApiController@getExternalOrder');
            Route::post('{external_order_id}/external-attendees/{external_attendee_id}/confirm', 'OAuth2SummitApiController@confirmExternalOrderAttendee');
        });

        // members
        Route::group(array('prefix' => 'members'), function () {
            Route::get("", ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMembersApiController@getAllBySummit']);
            Route::get("csv", ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMembersApiController@getAllBySummitCSV']);
            Route::group(array('prefix' => '{member_id}'), function () {

                Route::get('', 'OAuth2SummitMembersApiController@getMyMember')->where('member_id', 'me');


                // favorites
                Route::group(['prefix' => 'favorites'], function () {
                    Route::get('', 'OAuth2SummitMembersApiController@getMemberFavoritesSummitEvents')->where('member_id', 'me');

                    Route::group(array('prefix' => '{event_id}'), function () {
                        Route::post('', 'OAuth2SummitMembersApiController@addEventToMemberFavorites')->where('member_id', 'me');
                        Route::delete('', 'OAuth2SummitMembersApiController@removeEventFromMemberFavorites')->where('member_id', 'me');
                    });
                });

                // schedule
                Route::group(array('prefix' => 'schedule'), function () {
                    Route::get('', 'OAuth2SummitMembersApiController@getMemberScheduleSummitEvents')->where('member_id', 'me');

                    Route::group(['prefix' => 'shareable-link'], function () {
                        Route::post('', 'OAuth2SummitMembersApiController@createScheduleShareableLink')->where('member_id', 'me');
                        Route::delete('', 'OAuth2SummitMembersApiController@revokeScheduleShareableLink')->where('member_id', 'me');
                    });

                    Route::group(array('prefix' => '{event_id}'), function () {

                        Route::group(['prefix' => 'rsvp'], function () {
                            Route::post('', 'OAuth2SummitMembersApiController@addEventRSVP')->where('member_id', 'me');
                            Route::put('', 'OAuth2SummitMembersApiController@updateEventRSVP')->where('member_id', 'me');
                            Route::delete('', 'OAuth2SummitMembersApiController@deleteEventRSVP')->where('member_id', 'me');
                        });

                        Route::group(['prefix' => 'feedback'], function () {
                            Route::get('', 'OAuth2SummitEventsApiController@getMyEventFeedback')->where('member_id', 'me');
                            Route::post('', 'OAuth2SummitEventsApiController@addMyEventFeedback')->where('member_id', 'me');
                            Route::put('', 'OAuth2SummitEventsApiController@updateMyEventFeedback')->where('member_id', 'me');
                            Route::delete('', 'OAuth2SummitEventsApiController@deleteMyEventFeedback')->where('member_id', 'me');
                        });

                        Route::post('', 'OAuth2SummitMembersApiController@addEventToMemberSchedule')->where('member_id', 'me');
                        Route::delete('', 'OAuth2SummitMembersApiController@removeEventFromMemberSchedule')->where('member_id', 'me');


                        Route::put('enter', 'OAuth2SummitMetricsApiController@enterToEvent')->where('member_id', 'me');
                        Route::post('leave', 'OAuth2SummitMetricsApiController@leaveFromEvent')->where('member_id', 'me');
                    });
                });
            });

        });

        // tracks
        Route::group(['prefix' => 'tracks'], function () {
            Route::get('', 'OAuth2SummitTracksApiController@getAllBySummit');
            Route::get('csv', 'OAuth2SummitTracksApiController@getAllBySummitCSV');
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTracksApiController@addTrackBySummit']);
            Route::post('copy/{to_summit_id}', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTracksApiController@copyTracksToSummit']);
            Route::group(['prefix' => '{track_id}'], function () {
                Route::get('', 'OAuth2SummitTracksApiController@getTrackBySummit');
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTracksApiController@updateTrackBySummit']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTracksApiController@deleteTrackBySummit']);

                Route::group(['prefix' => 'allowed-tags'], function () {
                    Route::get('', 'OAuth2SummitTracksApiController@getTrackAllowedTagsBySummit');
                });

                Route::group(['prefix' => 'icon'], function () {
                    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTracksApiController@addTrackIcon']);
                    Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTracksApiController@deleteTrackIcon']);
                });

                Route::group(['prefix' => 'extra-questions'], function () {
                    Route::get('', 'OAuth2SummitTracksApiController@getTrackExtraQuestionsBySummit');
                    Route::group(['prefix' => '{question_id}'], function () {

                        Route::put('', [
                                'middleware' => 'auth.user',
                                'uses' => 'OAuth2SummitTracksApiController@addTrackExtraQuestion']
                        );

                        Route::delete('', [
                            'middleware' => 'auth.user',
                            'uses' => 'OAuth2SummitTracksApiController@removeTrackExtraQuestion'
                        ]);
                    });
                });

                Route::group(['prefix' => 'proposed-schedule-allowed-locations'], function () {
                    Route::get('', [
                            'middleware' => 'auth.user',
                            'uses' => 'OAuth2SummitProposedScheduleAllowedLocationApiController@getAllAllowedLocationByTrack']
                    );
                    Route::post('', [
                            'middleware' => 'auth.user',
                            'uses' => 'OAuth2SummitProposedScheduleAllowedLocationApiController@addAllowedLocationToTrack']
                    );
                    Route::group(['prefix' => 'all'], function () {
                        Route::delete('', [
                                'middleware' => 'auth.user',
                                'uses' => 'OAuth2SummitProposedScheduleAllowedLocationApiController@removeAllAllowedLocationFromTrack']
                        );
                    });
                    Route::group(['prefix' => '{location_id}'], function () {
                        Route::get('', [
                                'middleware' => 'auth.user',
                                'uses' => 'OAuth2SummitProposedScheduleAllowedLocationApiController@getAllowedLocationFromTrack']
                        );
                        Route::delete('', [
                                'middleware' => 'auth.user',
                                'uses' => 'OAuth2SummitProposedScheduleAllowedLocationApiController@removeAllowedLocationFromTrack']
                        );
                        Route::group(['prefix' => 'allowed-time-frames'], function () {
                            Route::get('', [
                                    'middleware' => 'auth.user',
                                    'uses' => 'OAuth2SummitProposedScheduleAllowedLocationApiController@getAllTimeFrameFromAllowedLocation']
                            );
                            Route::post('', [
                                    'middleware' => 'auth.user',
                                    'uses' => 'OAuth2SummitProposedScheduleAllowedLocationApiController@addTimeFrame2AllowedLocation']
                            );

                            Route::group(['prefix' => 'all'], function () {
                                Route::delete('', [
                                        'middleware' => 'auth.user',
                                        'uses' => 'OAuth2SummitProposedScheduleAllowedLocationApiController@removeAllTimeFrameFromAllowedLocation']
                                );
                            });

                            Route::group(['prefix' => '{time_frame_id}'], function () {
                                Route::get('', [
                                        'middleware' => 'auth.user',
                                        'uses' => 'OAuth2SummitProposedScheduleAllowedLocationApiController@getTimeFrameFromAllowedLocation']
                                );

                                Route::put('', [
                                        'middleware' => 'auth.user',
                                        'uses' => 'OAuth2SummitProposedScheduleAllowedLocationApiController@updateTimeFrameFromAllowedLocation']
                                );

                                Route::delete('', [
                                        'middleware' => 'auth.user',
                                        'uses' => 'OAuth2SummitProposedScheduleAllowedLocationApiController@removeTimeFrameFromAllowedLocation']
                                );

                            });
                        });
                    });
                });

                Route::group(['prefix' => 'sub-tracks'], function () {
                    Route::group(['prefix' => '{child_track_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTracksApiController@addSubTrack']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTracksApiController@removeSubTrack']);
                    });
                });
            });
        });

        // track chairs
        Route::group(['prefix' => 'track-chairs'], function () {
            Route::get('', [
                'middleware' => 'auth.user',
                'uses' => 'OAuth2SummitTrackChairsApiController@getAllBySummit'
            ]);

            Route::get('csv', [
                'middleware' => 'auth.user',
                'uses' => 'OAuth2SummitTrackChairsApiController@getAllBySummitCSV'
            ]);

            Route::post('', [
                'middleware' => 'auth.user',
                'uses' => 'OAuth2SummitTrackChairsApiController@add'
            ]);

            Route::group(['prefix' => '{track_chair_id}'], function () {

                Route::get('', [
                    'middleware' => 'auth.user',
                    'uses' => 'OAuth2SummitTrackChairsApiController@get'
                ]);

                Route::put('', [
                    'middleware' => 'auth.user',
                    'uses' => 'OAuth2SummitTrackChairsApiController@update'
                ]);
                Route::delete('', [
                    'middleware' => 'auth.user',
                    'uses' => 'OAuth2SummitTrackChairsApiController@delete'
                ]);

                Route::group(['prefix' => 'categories'], function () {
                    Route::group(['prefix' => '{track_id}'], function () {
                        Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTrackChairsApiController@addTrack2TrackChair']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitTrackChairsApiController@removeFromTrackChair']);
                    });
                });
            });
        });

        // track groups
        Route::group(['prefix' => 'track-groups'], function () {
            Route::get('', 'OAuth2PresentationCategoryGroupController@getAllBySummit');
            Route::get('csv', 'OAuth2PresentationCategoryGroupController@getAllBySummitCSV');
            Route::get('metadata', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationCategoryGroupController@getMetadata']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationCategoryGroupController@addTrackGroupBySummit']);

            Route::group(['prefix' => '{track_group_id}'], function () {
                Route::get('', 'OAuth2PresentationCategoryGroupController@getTrackGroupBySummit');
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationCategoryGroupController@updateTrackGroupBySummit']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationCategoryGroupController@deleteTrackGroupBySummit']);

                Route::group(['prefix' => 'tracks'], function () {

                    Route::group(['prefix' => '{track_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationCategoryGroupController@associateTrack2TrackGroup']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationCategoryGroupController@disassociateTrack2TrackGroup']);
                    });
                });
                Route::group(['prefix' => 'allowed-groups'], function () {

                    Route::group(['prefix' => '{group_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationCategoryGroupController@associateAllowedGroup2TrackGroup']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2PresentationCategoryGroupController@disassociateAllowedGroup2TrackGroup']);
                    });
                });
            });
        });

        // promo codes
        Route::group(['prefix' => 'promo-codes'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@getAllBySummit']);
            Route::group(['prefix' => 'csv'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@getAllBySummitCSV']);
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@ingestPromoCodes']);
            });
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@addPromoCodeBySummit']);
            Route::get('metadata', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@getMetadata']);
            Route::group(['prefix' => '{promo_code_id}', 'where' => ['source' => '[0-9]+']], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@getPromoCodeBySummit']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@updatePromoCodeBySummit']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@deletePromoCodeBySummit']);
                Route::post('mail', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@sendPromoCodeMail']);
                Route::group(['prefix' => 'badge-features'], function () {
                    Route::group(['prefix' => '{badge_feature_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@addBadgeFeatureToPromoCode']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@removeBadgeFeatureFromPromoCode']);
                    });
                });

                Route::group(['prefix' => 'ticket-types'], function () {
                    Route::group(['prefix' => '{ticket_type_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@addTicketTypeToPromoCode']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@removeTicketTypeFromPromoCode']);
                    });
                });
            });

            // 25 requests per minute
            Route::get('{promo_code_val}/apply', ['middleware' => ['rate.limit:25,1'], 'uses' => 'OAuth2SummitPromoCodesApiController@preValidatePromoCode']);
        });

        // sponsor promo codes
        Route::group(['prefix' => 'sponsor-promo-codes'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@getAllSponsorPromoCodesBySummit']);
            Route::group(['prefix' => 'csv'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@getSponsorPromoCodesAllBySummitCSV']);
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@ingestSponsorPromoCodes']);

            });
        });

        // speakers promo codes
        Route::group(['prefix' => 'speakers-promo-codes'], function () {
            Route::group(['prefix' => '{promo_code_id}'], function () {
                Route::group(['prefix' => 'speakers'], function () {
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@getPromoCodeSpeakers']);
                    Route::group(['prefix' => '{speaker_id}'], function () {
                        Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@addSpeaker']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@removeSpeaker']);
                    });
                });
            });
        });

        // speakers discount codes
        Route::group(['prefix' => 'speakers-discount-codes'], function () {
            Route::group(['prefix' => '{discount_code_id}'], function () {
                Route::group(['prefix' => 'speakers'], function () {
                    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@getDiscountCodeSpeakers']);
                    Route::group(['prefix' => '{speaker_id}'], function () {
                        Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@addSpeaker']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPromoCodesApiController@removeSpeaker']);
                    });
                });
            });
        });

        // track tag groups
        Route::group(['prefix' => 'track-tag-groups'], function () {

            Route::get('', ['uses' => 'OAuth2SummitTrackTagGroupsApiController@getTrackTagGroupsBySummit']);

            Route::post('', ['middleware' => 'auth.user',
                'uses' => 'OAuth2SummitTrackTagGroupsApiController@addTrackTagGroup']);

            Route::post('seed-defaults', ['middleware' => 'auth.user',
                'uses' => 'OAuth2SummitTrackTagGroupsApiController@seedDefaultTrackTagGroups']);

            Route::group(['prefix' => '{track_tag_group_id}'], function () {
                Route::get('', ['middleware' => 'auth.user',
                    'uses' => 'OAuth2SummitTrackTagGroupsApiController@getTrackTagGroup']);
                Route::put('', ['middleware' => 'auth.user',
                    'uses' => 'OAuth2SummitTrackTagGroupsApiController@updateTrackTagGroup']);
                Route::delete('', ['middleware' => 'auth.user',
                    'uses' => 'OAuth2SummitTrackTagGroupsApiController@deleteTrackTagGroup']);

                Route::group(['prefix' => 'allowed-tags'], function () {

                    Route::group(['prefix' => 'all'], function () {
                        Route::post('copy/tracks/{track_id}',
                            ['middleware' => 'auth.user',
                                'uses' => 'OAuth2SummitTrackTagGroupsApiController@seedTagTrackGroupOnTrack']);
                    });
                });

            });

            Route::group(['prefix' => 'all'], function () {
                Route::group(['prefix' => 'allowed-tags'], function () {

                    Route::get('', ['middleware' => 'auth.user',
                        'uses' => 'OAuth2SummitTrackTagGroupsApiController@getAllowedTags']);


                    Route::group(['prefix' => '{tag_id}'], function () {
                        Route::post('seed-on-tracks',
                            ['middleware' => 'auth.user',
                                'uses' => 'OAuth2SummitTrackTagGroupsApiController@seedTagOnAllTracks']);
                    });
                });
            });
        });

        // email-flows-events
        Route::group(['prefix' => 'email-flows-events'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEmailEventFlowApiController@getAllBySummit']);
            Route::group(['prefix' => '{event_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEmailEventFlowApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEmailEventFlowApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitEmailEventFlowApiController@delete']);
            });
        });

        // media-upload-types

        Route::group(['prefix' => 'media-upload-types'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaUploadTypeApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaUploadTypeApiController@add']);
            Route::group(['prefix' => '{media_upload_type_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaUploadTypeApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaUploadTypeApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaUploadTypeApiController@delete']);
                Route::group(['prefix' => 'presentation-types'], function () {
                    Route::group(['prefix' => '{event_type_id}'], function () {
                        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaUploadTypeApiController@addToPresentationType']);
                        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaUploadTypeApiController@deleteFromPresentationType']);
                    });
                });
            });

            Route::group(['prefix' => 'all'], function () {
                Route::post('clone/{to_summit_id}', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitMediaUploadTypeApiController@cloneMediaUploadTypes']);
            });
        });

        // featured speakers

        Route::group(['prefix' => 'featured-speakers'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@getAllFeatureSpeaker']);
            Route::group(['prefix' => '{speaker_id}'], function () {
                Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@addFeatureSpeaker']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@updateFeatureSpeaker']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@removeFeatureSpeaker']);
            });
        });

        // presentation action types

        Route::group(['prefix' => 'presentation-action-types'], function () {
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPresentationActionTypeApiController@getAllBySummit']);
            Route::get('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPresentationActionTypeApiController@getAllBySummitCSV']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPresentationActionTypeApiController@add']);
            Route::group(['prefix' => '{action_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPresentationActionTypeApiController@get']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPresentationActionTypeApiController@update']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitPresentationActionTypeApiController@delete']);
            });
        });

        // registration companies

        Route::group(['prefix' => 'registration-companies'], function () {
            Route::get('', ['uses' => 'OAuth2SummitRegistrationCompaniesApiController@getAllBySummit']);
            Route::post('csv', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationCompaniesApiController@import']);
            Route::group(['prefix' => '{company_id}'], function () {
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationCompaniesApiController@add']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationCompaniesApiController@delete']);
            });
        });

        // proposed schedule

        Route::group(['prefix' => 'proposed-schedules'], function () {
            Route::group(['prefix' => '{source}', 'where' => ['source' => '[a-z0-9\-]+']], function () {
                Route::group(['prefix' => 'presentations'], function () {

                    Route::get('', ['uses' => 'OAuth2SummitProposedScheduleApiController@getProposedScheduleEvents']);

                    Route::group(['prefix' => 'all'], function () {
                        Route::put('publish', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitProposedScheduleApiController@publishAll']);
                    });

                    Route::group(['prefix' => '{presentation_id}'], function () {
                        Route::group(['prefix' => 'propose'], function () {
                            Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitProposedScheduleApiController@publish']);
                            Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitProposedScheduleApiController@unpublish']);
                        });
                    });
                });

                Route::group(['prefix' => 'tracks'], function () {
                    Route::group(['prefix' => '{track_id}'], function () {
                        Route::group(['prefix' => 'lock'], function () {
                            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitProposedScheduleApiController@send2Review']);
                            Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitProposedScheduleApiController@removeReview']);
                        });
                    });
                });

                Route::group(['prefix' => 'locks'], function () {
                    Route::get('', ['uses' => 'OAuth2SummitProposedScheduleApiController@getProposedScheduleReviewSubmissions']);
                });
            });
        });

        //qr-codes

        Route::group(['prefix' => 'qr-codes'], function () {
            Route::group(['prefix' => 'all'], function () {
                Route::put('enc-key', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@generateQREncKey']);
            });
        });

        // registration-feed-metadata

        Route::group(['prefix' => 'registration-feed-metadata'], function(){
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationFeedMetadataApiController@getAllBySummit']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationFeedMetadataApiController@add']);
            Route::group(['prefix' => '{metadata_id}'], function(){
                Route::put('', ['uses' => 'OAuth2SummitRegistrationFeedMetadataApiController@update']);
                Route::get('', ['uses' => 'OAuth2SummitRegistrationFeedMetadataApiController@get']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitRegistrationFeedMetadataApiController@delete']);
            });
        });

        // lead report settings

        Route::group(['prefix' => 'lead-report-settings'], function(){
            Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@getLeadReportSettings']);
            Route::get('metadata', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@getLeadReportSettingsMetadata']);
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@addLeadReportSettings']);
            Route::put('', ['uses' => 'OAuth2SummitApiController@updateLeadReportSettings']);
        });

        Route::group(['prefix' => 'badge'], function(){
            Route::group(['prefix' => '{badge}'], function(){
                Route::get('validate', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitApiController@validateBadge']);
            });
        });

        // add-ons types

        Route::group(['prefix' => 'add-ons'], function(){
            Route::get('metadata', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSponsorshipsApiController@getMetadata']);
        });
    });
});

// sponsorship-types
Route::group(['prefix' => 'sponsorship-types'], function () {
    Route::get('', ['uses' => 'OAuth2SponsorshipTypeApiController@getAll']);
    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsorshipTypeApiController@add']);
    Route::group(['prefix' => '{id}'], function () {
        Route::get('', ['uses' => 'OAuth2SponsorshipTypeApiController@get']);
        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsorshipTypeApiController@update']);
        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SponsorshipTypeApiController@delete']);
    });
});

// speakers
Route::group(['prefix' => 'speakers'], function () {

    Route::get('', 'OAuth2SummitSpeakersApiController@getAll');
    Route::post('', 'OAuth2SummitSpeakersApiController@addSpeaker');
    Route::put('merge/{speaker_from_id}/{speaker_to_id}', 'OAuth2SummitSpeakersApiController@merge');

    Route::group(['prefix' => 'active-involvements'], function () {
        Route::get('', 'OAuth2SpeakerActiveInvolvementApiController@getAll');
    });

    Route::group(['prefix' => 'organizational-roles'], function () {
        Route::get('', 'OAuth2SpeakerOrganizationalRoleApiController@getAll');
    });

    Route::group(['prefix' => 'me'], function () {
        Route::get('', 'OAuth2SummitSpeakersApiController@getMySpeaker');
        Route::post('', 'OAuth2SummitSpeakersApiController@createMySpeaker');
        Route::put('', 'OAuth2SummitSpeakersApiController@updateMySpeaker');

        // speaker photos
        Route::group(['prefix' => 'photo'], function () {
            Route::post('', ['uses' => 'OAuth2SummitSpeakersApiController@addMySpeakerPhoto']);
            Route::delete('', ['uses' => 'OAuth2SummitSpeakersApiController@deleteMySpeakerPhoto']);
        });

        Route::group(['prefix' => 'big-photo'], function () {
            Route::post('', ['uses' => 'OAuth2SummitSpeakersApiController@addMySpeakerBigPhoto']);
            Route::delete('', ['uses' => 'OAuth2SummitSpeakersApiController@deleteMySpeakerBigPhoto']);
        });

        Route::group(['prefix' => 'presentations'], function () {

            Route::group(['prefix' => '{presentation_id}'], function () {

                Route::group(['prefix' => 'speakers'], function () {
                    Route::put('{speaker_id}', 'OAuth2SummitSpeakersApiController@addSpeakerToMyPresentation');
                    Route::delete('{speaker_id}', 'OAuth2SummitSpeakersApiController@removeSpeakerFromMyPresentation');
                });
                Route::group(['prefix' => 'moderators'], function () {
                    Route::put('{speaker_id}', 'OAuth2SummitSpeakersApiController@addModeratorToMyPresentation');
                    Route::delete('{speaker_id}', 'OAuth2SummitSpeakersApiController@removeModeratorFromMyPresentation');
                });
            });
            Route::group(['prefix' => '{role}'], function () {
                Route::group(['prefix' => 'selection-plans'], function () {
                    Route::group(['prefix' => '{selection_plan_id}'], function () {
                        Route::get("", "OAuth2SummitSpeakersApiController@getMySpeakerPresentationsByRoleAndBySelectionPlan")
                            ->where('role', 'creator|speaker|moderator');
                    });
                });

                Route::group(['prefix' => 'summits'], function () {
                    Route::group(['prefix' => '{summit_id}'], function () {
                        Route::get("", "OAuth2SummitSpeakersApiController@getMySpeakerPresentationsByRoleAndBySummit")
                            ->where('role', 'creator|speaker|moderator');
                    });
                });
            });
        });
    });

    Route::group(['prefix' => '{speaker_id}'], function () {
        Route::put('/edit-permission', 'OAuth2SummitSpeakersApiController@requestSpeakerEditPermission')->where('speaker_id', '[0-9]+');
        Route::get('/edit-permission', 'OAuth2SummitSpeakersApiController@getSpeakerEditPermission')->where('speaker_id', '[0-9]+');
        Route::put('', 'OAuth2SummitSpeakersApiController@updateSpeaker')->where('speaker_id', 'me|[0-9]+');
        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitSpeakersApiController@deleteSpeaker'])->where('speaker_id', 'me|[0-9]+');
        Route::get('', ['middleware' =>
            sprintf('cache:%s,%s,speaker_id',
                3600,
                CacheRegions::CacheRegionSpeakers,
            ),
            'uses' =>'OAuth2SummitSpeakersApiController@getSpeaker']);
        // speaker photos
        Route::group(['prefix' => 'photo'], function () {
            Route::post('', ['uses' => 'OAuth2SummitSpeakersApiController@addSpeakerPhoto']);
            Route::delete('', ['uses' => 'OAuth2SummitSpeakersApiController@deleteSpeakerPhoto']);
        });

        Route::group(['prefix' => 'big-photo'], function () {
            Route::post('', ['uses' => 'OAuth2SummitSpeakersApiController@addSpeakerBigPhoto']);
            Route::delete('', ['uses' => 'OAuth2SummitSpeakersApiController@deleteSpeakerBigPhoto']);
        });

    });
});

// track question templates
Route::group(['prefix' => 'track-question-templates'], function () {

    Route::get('', [
        'middleware' => 'auth.user',
        'uses' => 'OAuth2TrackQuestionsTemplateApiController@getTrackQuestionTemplates']);
    Route::get('metadata', [
        'middleware' => 'auth.user',
        'uses' => 'OAuth2TrackQuestionsTemplateApiController@getTrackQuestionTemplateMetadata'
    ]);

    Route::post('', [
        'middleware' => 'auth.user',
        'uses' => 'OAuth2TrackQuestionsTemplateApiController@addTrackQuestionTemplate']);

    Route::group(['prefix' => '{track_question_template_id}'], function () {

        Route::get('', ['middleware' => 'auth.user',
            'uses' => 'OAuth2TrackQuestionsTemplateApiController@getTrackQuestionTemplate']);

        Route::put('', [
            'middleware' => 'auth.user',
            'uses' => 'OAuth2TrackQuestionsTemplateApiController@updateTrackQuestionTemplate']);

        Route::delete('', [
            'middleware' => 'auth.user',
            'uses' => 'OAuth2TrackQuestionsTemplateApiController@deleteTrackQuestionTemplate']);

        // multi values questions
        Route::group(['prefix' => 'values'], function () {
            Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2TrackQuestionsTemplateApiController@addTrackQuestionTemplateValue']);
            Route::group(['prefix' => '{track_question_template_value_id}'], function () {
                Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2TrackQuestionsTemplateApiController@getTrackQuestionTemplateValue']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2TrackQuestionsTemplateApiController@updateTrackQuestionTemplateValue']);
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2TrackQuestionsTemplateApiController@deleteTrackQuestionTemplateValue']);
            });
        });
    });
});

// summit-administrator-groups
Route::group(['prefix' => 'summit-administrator-groups'], function () {
    Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAdministratorPermissionGroupApiController@getAll']);
    Route::post('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAdministratorPermissionGroupApiController@add']);
    Route::group(['prefix' => '{group_id}'], function () {
        Route::get('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAdministratorPermissionGroupApiController@get']);
        Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAdministratorPermissionGroupApiController@delete']);
        Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAdministratorPermissionGroupApiController@update']);

        Route::group(['prefix' => 'members'], function () {
            Route::group(['prefix' => '{member_id}'], function () {
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAdministratorPermissionGroupApiController@addMember']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAdministratorPermissionGroupApiController@removeMember']);
            });
        });

        Route::group(['prefix' => 'summits'], function () {
            Route::group(['prefix' => '{summit_id}'], function () {
                Route::delete('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAdministratorPermissionGroupApiController@addSummit']);
                Route::put('', ['middleware' => 'auth.user', 'uses' => 'OAuth2SummitAdministratorPermissionGroupApiController@removeSummit']);
            });
        });
    });
});

// elections

Route::group(['prefix' => 'elections'], function () {
    Route::get('',  [ 'uses' => 'OAuth2ElectionsApiController@getAll']);
    Route::group(['prefix'=>'{election_id}'], function(){
        Route::get('',  [ 'uses' => 'OAuth2ElectionsApiController@getById']);
        Route::group(['prefix'=>'candidates'], function(){
            Route::get('',  [ 'uses' => 'OAuth2ElectionsApiController@getElectionCandidates']);
            Route::group(['prefix'=>'gold'], function(){
                Route::get('',  [ 'uses' => 'OAuth2ElectionsApiController@getElectionGoldCandidates']);
            });
        });
    });
    Route::group(['prefix' => 'current'], function () {
        Route::group(['prefix' => 'candidates'], function () {
            Route::group(['prefix' => 'me'], function () {
                Route::put('', ['uses' => 'OAuth2ElectionsApiController@updateMyCandidateProfile']);
            });
            Route::group(['prefix' => '{candidate_id}'], function () {
                Route::post('', ['uses' => 'OAuth2ElectionsApiController@nominateCandidate']);
            });
        });
    });
});




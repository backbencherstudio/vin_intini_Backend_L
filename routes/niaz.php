<?php

use App\Http\Controllers\Admin\IndustryApiController;
use App\Http\Controllers\Api\AcademiaController;
use App\Http\Controllers\Api\ConnectionController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\IndustryAnalyticsController;
use App\Http\Controllers\Api\IndustryDashboardController;
use App\Http\Controllers\Api\IndustryJobApplicationController;
use App\Http\Controllers\Api\IndustryJobPostController;
use App\Http\Controllers\Api\IndustryProductController;
use App\Http\Controllers\Api\IndustryProfileController;
use App\Http\Controllers\Api\JobsFeedController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProductBoostController;
use App\Http\Controllers\Api\SecuritySettingsController;
use App\Http\Controllers\Api\UserEducationController;
use App\Http\Controllers\Api\UserExperienceController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

// group routes
// Route::get('/groups-suggestions', [GroupController::class, 'groupSuggestions']);
Route::post('/group-create', [GroupController::class, 'store']);
Route::get('/group-show/{id}', [GroupController::class, 'show']);
Route::post('/group-update/{id}', [GroupController::class, 'update']);
Route::post('/group-images/{id}', [GroupController::class, 'updateImages']);
Route::post('/group/{id}/toggle-notification', [GroupController::class, 'toggleNotificationStatus']);
// my group routes
Route::get('/my-created-groups', [GroupController::class, 'myCreatedGroups']);
// my joined group routes
Route::get('/my-joined-groups', [GroupController::class, 'myJoinedGroups']);

Route::get('/group-list', [GroupController::class, 'groupList']);
// group member join and leave routes
Route::post('/group/join', [GroupController::class, 'joinGroup']);
Route::post('/group/leave', [GroupController::class, 'leaveGroup']);
// group invite routes
Route::get('/group-invite-users/{id}', [GroupController::class, 'inviteableUsers']);
Route::post('/group-invite-user/{id}', [GroupController::class, 'sendInvitation']);
Route::get('/group-invitations/requests', [GroupController::class, 'invitationRequests']);
Route::post('/group-invitations/{invitationId}/accept', [GroupController::class, 'acceptInvitation']);
Route::post('/group-invitations/{invitationId}/ignore', [GroupController::class, 'ignoreInvitation']);
// ban user route
Route::post('/groups/{groupId}/ban/{userId}', [GroupController::class, 'banUser']);

// user profile routes
Route::get('/profile/{username}', [UserProfileController::class, 'showUserProfile']); // others user profile
Route::get('/profile', [UserProfileController::class, 'show']); // current user profile
Route::put('/profile/update', [UserProfileController::class, 'update']);
Route::post('/profile/images', [UserProfileController::class, 'updateImages']);
Route::post('/profile/change-password', [UserProfileController::class, 'changePassword']);

// user experience routes
Route::get('/experience/list', [UserExperienceController::class, 'index']); // list of user experience (auth user)
Route::post('/experience/add', [UserExperienceController::class, 'store']);
Route::get('/experience/edit/{id}', [UserExperienceController::class, 'edit']);
Route::post('/experience/update/{id}', [UserExperienceController::class, 'update']);
Route::delete('/experience/delete/{id}', [UserExperienceController::class, 'destroy']);
Route::get('/user-experiences/{identifier}', [UserExperienceController::class, 'showExperienceByUserId']);

// user education routes
Route::get('/education/list', [UserEducationController::class, 'index']); // list of user education (auth user)
Route::post('/education/add', [UserEducationController::class, 'store']);
Route::get('/education/edit/{id}', [UserEducationController::class, 'edit']);
Route::post('/education/update/{id}', [UserEducationController::class, 'update']);
Route::delete('/education/delete/{id}', [UserEducationController::class, 'destroy']);
Route::get('/user-educations/{identifier}', [UserEducationController::class, 'showEducationByUserId']);

// user connection routes
Route::get('/connections', [ConnectionController::class, 'index']);
Route::get('/connections/suggestions', [ConnectionController::class, 'suggestions']);
Route::get('/connections/sent-requests', [ConnectionController::class, 'sentPendingRequests']);
Route::get('/connections/requests', [ConnectionController::class, 'requests']);
Route::delete('/connections/{identifier}/remove', [ConnectionController::class, 'removeConnection']);
Route::post('/connections/request', [ConnectionController::class, 'sendRequest']);
Route::post('/connections/requests/{connectionRequest}/accept', [ConnectionController::class, 'accept']);
Route::post('/connections/requests/{connectionRequest}/ignore', [ConnectionController::class, 'ignore']);

// followers and following routes
Route::get('/connections/followers', [FollowController::class, 'followers']);
Route::get('/connections/following', [FollowController::class, 'following']);
Route::post('/connections/{identifier}/follow', [FollowController::class, 'follow']);
Route::delete('/connections/{identifier}/unfollow', [FollowController::class, 'unfollow']);

// notification routes
Route::get('/notifications', [NotificationController::class, 'index']);
Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
Route::post('/notifications/{notificationId}/mark-as-read', [NotificationController::class, 'markAsRead']);
Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
Route::delete('/notifications/{notificationId}', [NotificationController::class, 'delete']);
Route::delete('/notifications', [NotificationController::class, 'deleteAll']);

// academia routes
Route::get('/states', [AcademiaController::class, 'getStates']);
Route::get('/states/{code}', [AcademiaController::class, 'getStateDetails']);
Route::get('/states/{code}/cities', [AcademiaController::class, 'getCities']);
Route::get('/states/{code}/universities', [AcademiaController::class, 'getUniversities']);
Route::get('/states/{code}/residencies', [AcademiaController::class, 'getResidencies']);
Route::get('/states/{code}/facilities', [AcademiaController::class, 'getFacilities']); // medical facilities
Route::get('/states/{code}/jobs', [AcademiaController::class, 'getJobs']);

// industry routes
Route::prefix('psychology-network/industry')->group(function () {
    Route::get('/biotech', [IndustryApiController::class, 'getPsychologyBiotech']);
    Route::get('/pharma', [IndustryApiController::class, 'getPsychologyPharma']);
    Route::get('/publications', [IndustryApiController::class, 'getPsychologyPublications']);
    // Partners (Separate)
    Route::get('/biotech-partners', [IndustryApiController::class, 'getPsychologyBiotechPartners']);
    Route::get('/pharma-partners', [IndustryApiController::class, 'getPsychologyPharmaPartners']);
    Route::get('/pub-partners', [IndustryApiController::class, 'getPsychologyPubPartners']);
});

Route::prefix('neuroscience-network/industry')->group(function () {
    Route::get('/biotech', [IndustryApiController::class, 'getNeuroscienceBiotech']);
    Route::get('/pharma', [IndustryApiController::class, 'getNeurosciencePharma']);
    Route::get('/publications', [IndustryApiController::class, 'getNeurosciencePublications']);
    // Partners (Separate)
    Route::get('/biotech-partners', [IndustryApiController::class, 'getNeuroscienceBiotechPartners']);
    Route::get('/pharma-partners', [IndustryApiController::class, 'getNeurosciencePharmaPartners']);
    Route::get('/pub-partners', [IndustryApiController::class, 'getNeurosciencePubPartners']);
});

// account privacy settings route
Route::patch('/user/privacy-settings', [SecuritySettingsController::class, 'updatePrivacySettings']);

// account deletion request route
Route::post('/account/delete-request', [SecuritySettingsController::class, 'requestDelete']);

// pro Industry job post
Route::prefix('industry')->group(function () {
    Route::get('jobsfeed', [JobsFeedController::class, 'jobsFeed']); // global route
    Route::get('jobs-by-state-offering', [JobsFeedController::class, 'jobsByStateAndOffering']); // jobs filtered by state_id & employment_offering
    Route::get('jobs-count-by-state', [JobsFeedController::class, 'jobsCountByState']); // active jobs count by state (filterable by network_type)
    Route::get('job-post/{id_or_slug}', [IndustryJobPostController::class, 'show']); // global show route

    // recruiter dashboard
    Route::get('recruiter-dashboard', [IndustryDashboardController::class, 'index']);

    // industry job post routes for the authenticated user (creator)
    Route::get('my-job-posts', [IndustryJobPostController::class, 'myJobs']); // my created job list
    Route::get('my-archived-job-posts', [IndustryJobPostController::class, 'myArchivedJobs']); // created my archive job
    Route::post('job-post/create', [IndustryJobPostController::class, 'store']); // create a new job post
    Route::put('job-post/{id}/update', [IndustryJobPostController::class, 'update']); // update a job post
    Route::delete('job-post/{id}/delete', [IndustryJobPostController::class, 'destroy']); // delete a job post
    Route::patch('job-post/{id}/status', [IndustryJobPostController::class, 'updateStatus']);  // update job post status

    // user interaction routes for job posts (like, save, apply)
    Route::post('job-post/{id}/like', [IndustryJobPostController::class, 'toggleLike']); // like job post
    Route::post('job-post/{id}/save', [IndustryJobPostController::class, 'toggleSave']); // save/unsave job post
    Route::get('saved-jobs', [IndustryJobPostController::class, 'savedJobs']); // get saved jobs for current user

    // user application routes for job posts
    Route::get('my-job-applications', [IndustryJobApplicationController::class, 'myApplications']); // get all applications of the current user
    Route::post('job-post/{id}/apply', [IndustryJobApplicationController::class, 'applyJob']); // apply for a job post

    // routes for job applicants (for the creator or industry of the job post)
    Route::get('job-post/{job_id}/applicants', [IndustryJobApplicationController::class, 'jobApplicants']); // get all applicants for a job post
    Route::get('job-applications/{id}', [IndustryJobApplicationController::class, 'showApplication']); // show single application details
    Route::patch('job-application/{id}/status', [IndustryJobApplicationController::class, 'updateApplicationStatus']); // update application status (shortlist, reject, hire)
});

// industry analytics routes
Route::prefix('industry/analytics')->group(function () {
    Route::get('/job-overviews', [IndustryAnalyticsController::class, 'jobOverview']);
    Route::get('/advertisements', [IndustryAnalyticsController::class, 'advertisements']);
    Route::get('/profile-insights', [IndustryAnalyticsController::class, 'profileInsights']);
    Route::get('/publications-insights', [IndustryAnalyticsController::class, 'publicationsInsights']);
});

// Pro Industry Products
Route::prefix('industry')->group(function () {
    // Public / User Feed & Interactions
    Route::get('product-feed', [IndustryProductController::class, 'productFeed']);
    Route::get('feed', [IndustryProductController::class, 'productFeed']);
    Route::get('products/feed', [IndustryProductController::class, 'productFeed']);
    Route::get('products/{id}/details', [IndustryProductController::class, 'show']);
    Route::post('products/{id}/like', [IndustryProductController::class, 'toggleLike']);

    // Boost Packages for Advertisers (Modal & Campaign Builder)
    Route::get('boost-packages', [ProductBoostController::class, 'packages']);
    Route::get('boost-campaigns', [ProductBoostController::class, 'myCampaigns']);

    // Pro Industry Products Dashboard & Management
    Route::prefix('products')->group(function () {
        Route::get('dashboard', [IndustryProductController::class, 'dashboard']);
        Route::get('my-listings', [IndustryProductController::class, 'myListings']);
        Route::get('sections', [IndustryProductController::class, 'dropdownSections']);
        Route::get('sections/{id}/categories', [IndustryProductController::class, 'sectionCategories']);
        Route::post('create', [IndustryProductController::class, 'store']);
        Route::get('{id}', [IndustryProductController::class, 'editData']);
        Route::match(['put', 'post'], '{id}/update', [IndustryProductController::class, 'update']);
        Route::match(['patch', 'post'], '{id}/status', [IndustryProductController::class, 'updateStatus']);
        Route::delete('{id}/delete', [IndustryProductController::class, 'destroy']);

        // Product Boosting (Stripe Elements direct card payment & RevenueCat IAP)
        Route::get('{id}/boost/order-summary', [ProductBoostController::class, 'orderSummary']);
        Route::post('{id}/boost/send-otp', [ProductBoostController::class, 'sendOtp']);
        Route::post('{id}/boost/resend-otp', [ProductBoostController::class, 'resendOtp']);
        Route::post('{id}/boost/purchase', [ProductBoostController::class, 'purchase']);
        Route::post('{id}/boost/confirm', [ProductBoostController::class, 'confirmPayment']);
        Route::get('{id}/boost/campaigns', [ProductBoostController::class, 'campaigns']);
    });

    // Boost Campaign Pause & Resume Controls (Clock-Freeze)
    Route::post('boost/campaigns/{id}/pause', [ProductBoostController::class, 'pauseCampaign']);
    Route::post('boost/campaigns/{id}/resume', [ProductBoostController::class, 'resumeCampaign']);

    // Universal Ad Tracking (Client-side verified IAB viewable impressions & click recording)
    Route::post('advertisements/impressions', [ProductBoostController::class, 'recordImpressions']);
    Route::post('advertisements/{id}/click', [ProductBoostController::class, 'recordClick']);
});

// Industry Profile Tab Routes (Job Posts, Products Advertisement, Publications with Cursor Pagination)
Route::prefix('industry')->group(function () {
    Route::get('{identifier}/jobs', [IndustryProfileController::class, 'jobs']);
    Route::get('{identifier}/products', [IndustryProfileController::class, 'products']);
    Route::get('{identifier}/publications', [IndustryProfileController::class, 'publications']);
    Route::get('{identifier}/related', [IndustryProfileController::class, 'relatedIndustries']);
});

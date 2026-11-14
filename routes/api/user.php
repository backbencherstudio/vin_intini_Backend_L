<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\IndustryApiController;
use App\Http\Controllers\Api\AcademiaController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ConnectionController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\IndustryAnalyticsController;
use App\Http\Controllers\Api\IndustryController;
use App\Http\Controllers\Api\IndustryConversationController;
use App\Http\Controllers\Api\IndustryDashboardController;
use App\Http\Controllers\Api\IndustryJobApplicationController;
use App\Http\Controllers\Api\IndustryJobPostController;
use App\Http\Controllers\Api\IndustryMessageController;
use App\Http\Controllers\Api\IndustryProductController;
use App\Http\Controllers\Api\IndustryProfileController;
use App\Http\Controllers\Api\JobsFeedController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NewsfeedController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProductBoostController;
use App\Http\Controllers\Api\PublicationController;
use App\Http\Controllers\Api\SecuritySettingsController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TimelineController;
use App\Http\Controllers\Api\UserEducationController;
use App\Http\Controllers\Api\UserExperienceController;
use App\Http\Controllers\Api\UserJobPreferenceController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

// Group, company, skill and institution suggestions for dropdown
Route::get('/company-suggestions', [UserExperienceController::class, 'companySuggestions']);
Route::get('/skill-suggestions', [UserExperienceController::class, 'skillSuggestions']);
Route::get('/institution-suggestions', [UserEducationController::class, 'institutionSuggestions']);
Route::get('/groups-suggestions', [GroupController::class, 'groupSuggestions']);

Route::post('/setup-profile', [UserProfileController::class, 'setupProfile'])->middleware('verified_user');

Route::middleware('profile_completed')->group(function (): void {
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
    Route::post('/group-invite-users/{id}', [GroupController::class, 'sendInvitation']);
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

    // academia routes (states & cities are registered publicly in api.php)
    Route::get('/states/{code}/universities', [AcademiaController::class, 'getUniversities']);
    Route::get('/states/{code}/residencies', [AcademiaController::class, 'getResidencies']);
    Route::get('/states/{code}/post-baccalaureate-programs', [AcademiaController::class, 'getPostBaccalaureatePrograms']);
    Route::get('/states/{code}/facilities', [AcademiaController::class, 'getFacilities']); // medical facilities
    Route::get('/states/{code}/jobs', [AcademiaController::class, 'getJobs']);
    Route::get('/academia/post-baccalaureate/categories', [AcademiaController::class, 'getPostBaccalaureateCategories']);

    // industry routes
    Route::prefix('psychology-network/industry')->group(function (): void {
        Route::get('/biotech', [IndustryApiController::class, 'getPsychologyBiotech']);
        Route::get('/pharma', [IndustryApiController::class, 'getPsychologyPharma']);
        Route::get('/publications', [IndustryApiController::class, 'getPsychologyPublications']);
        // Unified & backward-compatible partners routes
        Route::get('/partners', [IndustryApiController::class, 'getAllPartners']);
        Route::get('/biotech-partners', [IndustryApiController::class, 'getPsychologyBiotechPartners']);
        Route::get('/pharma-partners', [IndustryApiController::class, 'getPsychologyPharmaPartners']);
        Route::get('/pub-partners', [IndustryApiController::class, 'getPsychologyPubPartners']);
    });

    Route::prefix('neuroscience-network/industry')->group(function (): void {
        Route::get('/biotech', [IndustryApiController::class, 'getNeuroscienceBiotech']);
        Route::get('/pharma', [IndustryApiController::class, 'getNeurosciencePharma']);
        Route::get('/publications', [IndustryApiController::class, 'getNeurosciencePublications']);
        // Unified & backward-compatible partners routes
        Route::get('/partners', [IndustryApiController::class, 'getAllPartners']);
        Route::get('/biotech-partners', [IndustryApiController::class, 'getNeuroscienceBiotechPartners']);
        Route::get('/pharma-partners', [IndustryApiController::class, 'getNeurosciencePharmaPartners']);
        Route::get('/pub-partners', [IndustryApiController::class, 'getNeurosciencePubPartners']);
    });

    // account privacy settings route
    Route::patch('/user/privacy-settings', [SecuritySettingsController::class, 'updatePrivacySettings']);

    // account deletion request route
    Route::post('/account/delete-request', [SecuritySettingsController::class, 'requestDelete']);

    // user job preference routes
    Route::get('/user/job-preference', [UserJobPreferenceController::class, 'getPreference']);
    Route::post('/user/job-preference', [UserJobPreferenceController::class, 'savePreference']);
    Route::delete('/user/job-preference', [UserJobPreferenceController::class, 'clearPreference']);

    // pro Industry job post
    Route::prefix('industry')->group(function (): void {
        Route::get('jobsfeed', [JobsFeedController::class, 'jobsFeed']); // global route
        Route::get('jobsfeed/recommended', [JobsFeedController::class, 'recommendedJobs']); // recommended jobs based on preferences
        Route::get('jobs/recommended', [JobsFeedController::class, 'recommendedJobs']); // alias
        Route::get('jobs/top-picks', [JobsFeedController::class, 'topJobPicks']); // top job picks
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
    Route::prefix('industry/analytics')->group(function (): void {
        Route::get('/job-overviews', [IndustryAnalyticsController::class, 'jobOverview']);
        Route::get('/advertisements', [IndustryAnalyticsController::class, 'advertisements']);
        Route::get('/profile-insights', [IndustryAnalyticsController::class, 'profileInsights']);
        Route::get('/publications-insights', [IndustryAnalyticsController::class, 'publicationsInsights']);
    });

    // Pro Industry Products
    Route::prefix('industry')->group(function (): void {
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
        Route::prefix('products')->group(function (): void {
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
    Route::prefix('industry')->group(function (): void {
        Route::get('{identifier}/jobs', [IndustryProfileController::class, 'jobs']);
        Route::get('{identifier}/products', [IndustryProfileController::class, 'products']);
        Route::get('{identifier}/publications', [IndustryProfileController::class, 'publications']);
        Route::get('{identifier}/related', [IndustryProfileController::class, 'relatedIndustries']);
    });

    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/profile/posts/{id}', [PostController::class, 'editProfilePost']);
    Route::post('/profile/posts/{id}', [PostController::class, 'updateProfilePost']);

    Route::delete('/profile/posts/{id}', [PostController::class, 'destroyProfilePost']);

    Route::get('/groups/{group}/posts/{post}', [PostController::class, 'editGroupPost']);
    Route::post('/groups/{group}/posts/{post}', [PostController::class, 'updateGroupPost']);
    Route::delete('/groups/{group}/posts/{post}', [PostController::class, 'destroyGroupPost']);

    Route::get('/newsfeed', [NewsfeedController::class, 'newsFeed']);
    Route::get('/single-post/{id}', [NewsfeedController::class, 'singlePost']);
    Route::get('/timeline/{identifier}', [TimelineController::class, 'timeline']);

    Route::get('/group-posts/{groupId}', [TimelineController::class, 'groupPosts']);

    // Like

    Route::get('/liked-list/{post}', [LikeController::class, 'likedList']);
    Route::post('/toggle-like/{post}', [LikeController::class, 'toggleLike']);

    Route::get('/comment-liked-list/{comment}', [LikeController::class, 'commentLikedList']);
    Route::get('/reply-liked-list/{reply}', [LikeController::class, 'replyLikedList']);

    Route::post('/comment-toggle-like/{comment}', [LikeController::class, 'likeComment']);
    Route::post('/reply-toggle-like/{reply}', [LikeController::class, 'likeReply']);

    Route::get('/comment-list/{post}', [CommentController::class, 'commentList']);
    Route::get('/reply-list/{comment}', [CommentController::class, 'replyList']);
    Route::post('/comment/{post}', [CommentController::class, 'comment']);

    Route::delete('/comment/{id}', [CommentController::class, 'deleteComment']);
    Route::delete('/reply/{id}', [CommentController::class, 'deleteReply']);

    Route::get('/my-comment-list', [CommentController::class, 'myComments']);

    // conversation routes
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/unread-count', [ConversationController::class, 'unreadCount']);
    Route::post('/conversations/{conversation}/mark-read', [ConversationController::class, 'markAsRead']);
    Route::post('/conversations/{conversation}/mark-unread', [ConversationController::class, 'markAsUnread']);
    Route::post('/conversations/{conversation}/archive', [ConversationController::class, 'archive']);
    Route::post('/conversations/{conversation}/unarchive', [ConversationController::class, 'unarchive']);
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy']);
    Route::post('/conversations/with/{user}', [ConversationController::class, 'showOrCreate']);

    // message routes
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);
    Route::post('/messages/{message}/react', [MessageController::class, 'react']);
    Route::delete('/messages/{message}/react', [MessageController::class, 'unreact']);

    // industry conversation routes
    Route::get('/industry-conversations', [IndustryConversationController::class, 'index']);
    Route::get('/industry-conversations/unread-count', [IndustryConversationController::class, 'unreadCount']);
    Route::get('/industry-conversations/search-users', [IndustryConversationController::class, 'searchUsers']);
    Route::post('/industry-conversations/with-industry/{industry}', [IndustryConversationController::class, 'showOrCreateForUser']);
    Route::post('/industry-conversations/with-user/{user}', [IndustryConversationController::class, 'showOrCreateForIndustry']);
    Route::post('/industry-conversations/{conversation}/mark-read', [IndustryConversationController::class, 'markAsRead']);
    Route::post('/industry-conversations/{conversation}/mark-unread', [IndustryConversationController::class, 'markAsUnread']);
    Route::post('/industry-conversations/{conversation}/archive', [IndustryConversationController::class, 'archive']);
    Route::post('/industry-conversations/{conversation}/unarchive', [IndustryConversationController::class, 'unarchive']);
    Route::delete('/industry-conversations/{conversation}', [IndustryConversationController::class, 'destroy']);

    // industry message routes
    Route::get('/industry-conversations/{conversation}/messages', [IndustryMessageController::class, 'index']);
    Route::post('/industry-conversations/{conversation}/messages', [IndustryMessageController::class, 'store']);
    Route::delete('/industry-messages/{message}', [IndustryMessageController::class, 'destroy']);
    Route::post('/industry-messages/{message}/react', [IndustryMessageController::class, 'react']);
    Route::delete('/industry-messages/{message}/react', [IndustryMessageController::class, 'unreact']);

    // plans & subscription routes
    Route::get('/plans', [SubscriptionController::class, 'plans']);
    Route::get('/plans/{id}', [SubscriptionController::class, 'show']);
    Route::post('/subscriptions/send-otp', [SubscriptionController::class, 'sendOtp']);
    Route::post('/subscriptions/create', [SubscriptionController::class, 'create']);
    Route::get('/subscriptions/status', [SubscriptionController::class, 'status']);
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
    Route::get('/billing-history', [SubscriptionController::class, 'billingHistory']);

    // Publications Feed & Management
    Route::prefix('publications')->group(function (): void {
        // Public Reader Endpoints & Interactions
        Route::get('feed', [PublicationController::class, 'feed']);
        Route::get('{id}/details', [PublicationController::class, 'show']);
        Route::post('{id}/like', [PublicationController::class, 'toggleLike']);

        // Author / Dashboard Management Endpoints
        Route::get('my-listings', [PublicationController::class, 'myListings']);
        Route::post('create', [PublicationController::class, 'store']);
        Route::get('{id}', [PublicationController::class, 'editData']);
        Route::match(['put', 'post'], '{id}/update', [PublicationController::class, 'update']);
        Route::match(['patch', 'post'], '{id}/status', [PublicationController::class, 'updateStatus']);
        Route::delete('{id}/delete', [PublicationController::class, 'destroy']);
    });

});

// Industry Company Management and Interactions
Route::prefix('industry')->group(function (): void {
    Route::post('create', [IndustryController::class, 'store']);
    Route::get('show/{industryId}', [IndustryController::class, 'show']);
    Route::post('update', [IndustryController::class, 'update']);
    Route::delete('delete', [IndustryController::class, 'deleteCompany']);

    Route::post('follow/{industryId}', [IndustryController::class, 'toggleFollow']);

    // Industry post
    Route::post('post/create', [IndustryController::class, 'storePost']);
    Route::get('post/view/{industryId}', [IndustryController::class, 'indexPost']);
    Route::get('post/recent', [IndustryController::class, 'latestPosts']);
    Route::post('post/update/{postId}', [IndustryController::class, 'updatePost']);
    Route::delete('post/delete/{postId}', [IndustryController::class, 'deletePost']);

    Route::post('post/like/{postId}', [IndustryController::class, 'togglePostLike']);
    Route::get('post/likes/{postId}', [IndustryController::class, 'likeList']);

    Route::post('post/comment/{postId}', [IndustryController::class, 'storeComment']);
    Route::get('post/comments/{postId}', [IndustryController::class, 'commentList']);

    Route::post('post/comment/like/{commentId}', [IndustryController::class, 'toggleCommentLike']);
    Route::get('post/comment/likes/{commentId}', [IndustryController::class, 'commentLikeList']);

    Route::post('post/comment/reply/{commentId}', [IndustryController::class, 'replyComment']);
    Route::get('post/comment/replies/{commentId}', [IndustryController::class, 'replyList']);

    Route::post('post/comment/reply/like/{replyId}', [IndustryController::class, 'toggleReplyLike']);
    Route::get('post/comment/reply/likes/{replyId}', [IndustryController::class, 'replyLikeList']);

    Route::delete('post/comment/{commentId}', [IndustryController::class, 'deleteComment']);
});

<?php

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NewsfeedController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TimelineController;
use Illuminate\Support\Facades\Route;

Route::post('/posts', [PostController::class, 'store']);
Route::get('/profile/posts/{id}', [PostController::class, 'editProfilePost']);
Route::post('/profile/posts/{id}', [PostController::class, 'updateProfilePost']);

Route::delete('/profile/posts/{id}', [PostController::class, 'destroyProfilePost']);

Route::get('/groups/{group}/posts/{post}', [PostController::class, 'editGroupPost']);
Route::post('/groups/{group}/posts/{post}', [PostController::class, 'updateGroupPost']);
Route::delete('/groups/{group}/posts/{post}', [PostController::class, 'destroyGroupPost']);

Route::get('/newsfeed', [NewsfeedController::class, 'newsFeed']);
Route::get('/single-post/{id}', [NewsfeedController::class, 'singlePost']);
Route::get('/timeline/{userId}', [TimelineController::class, 'timeline']);

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

// plans & subscription routes
Route::get('/plans', [SubscriptionController::class, 'plans']);
Route::post('/subscriptions/send-otp', [SubscriptionController::class, 'sendOtp']);
Route::post('/subscriptions/create', [SubscriptionController::class, 'create']);
Route::get('/subscriptions/status', [SubscriptionController::class, 'status']);

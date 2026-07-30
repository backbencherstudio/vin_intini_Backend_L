<?php

namespace App\Enums;

enum PlanFeature: string
{
    case SEARCH_PROFILES = 'search_profiles';
    case PROFILE_VIEW_VISIBILITY = 'profile_view_visibility';
    case ENDORSEMENTS = 'endorsements_recommendations';
    case BUILD_NETWORK = 'build_network';
    case SEND_CONNECTION_REQUEST = 'send_connection_request';
    case UNLIMITED_MESSAGING = 'unlimited_direct_messaging';
    case JOIN_COLLABORATION_GROUP = 'join_collaboration_group';
    case POSTS_MEDIA = 'posts_articles_photos_videos';
    case JOB_SEARCH = 'job_search';
    case JOB_APPLICATIONS = 'job_applications';
    case JOB_ALERTS = 'job_alerts';
    case UNLIMITED_INMAIL = 'unlimited_inmail';
    case SAVED_SEARCHES = 'saved_searches_alerts';
    case INTERACTIVE_MEDIA = 'interactive_media';
    case PROFILE_VIEWER_INSIGHTS = 'profile_viewer_insights';
    case RECEIVE_UNLIMITED_MESSAGES = 'receive_unlimited_messages';
    case CONNECT_ORGANIZATIONS = 'connect_with_organizations';
    case PRODUCT_ADVERTISEMENT = 'product_advertisement';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::SEARCH_PROFILES->value => 'Search Profiles',
            self::PROFILE_VIEW_VISIBILITY->value => 'Profile View Visibility',
            self::ENDORSEMENTS->value => 'Endorsements & Recommendations',
            self::BUILD_NETWORK->value => 'Build Network',
            self::SEND_CONNECTION_REQUEST->value => 'Send Connection Request',
            self::UNLIMITED_MESSAGING->value => 'Unlimited Direct Messaging',
            self::JOIN_COLLABORATION_GROUP->value => 'Join Collaboration Group',
            self::POSTS_MEDIA->value => 'Posts, Articles, Photos & Videos',
            self::JOB_SEARCH->value => 'Job Search',
            self::JOB_APPLICATIONS->value => 'Job Applications',
            self::JOB_ALERTS->value => 'Job Alerts',
            self::UNLIMITED_INMAIL->value => ' Unlimited InMail',
            self::SAVED_SEARCHES->value => 'Saved Searches & Alerts',
            self::INTERACTIVE_MEDIA->value => 'Interactive Media',
            self::PROFILE_VIEWER_INSIGHTS->value => 'Profile Viewer Insights',
            self::RECEIVE_UNLIMITED_MESSAGES->value => 'Receive Unlimited Messages',
            self::CONNECT_ORGANIZATIONS->value => 'Connect with Organizations',
            self::PRODUCT_ADVERTISEMENT->value => 'Product Advertisement',
        ];
    }
}

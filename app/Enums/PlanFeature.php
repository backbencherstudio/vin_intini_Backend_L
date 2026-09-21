<?php

namespace App\Enums;

enum PlanFeature: string
{
    case COMPANY_PROFILE = 'company_profile';
    case BUILD_NETWORK = 'build_network';
    case COLLABORATION_GROUPS = 'collaboration_groups';
    case DIRECT_MESSAGING = 'direct_messaging';
    case POSTS_ARTICLES_PHOTOS_VIDEOS = 'posts_articles_photos_videos';
    case PROFILE_VIEWS_INSIGHTS = 'profile_views_insights';
    case JOB_APPLICATIONS = 'job_applications';
    case CONNECT_ORGANIZATIONS = 'connect_organizations';
    case PRODUCT_ADVERTISEMENT = 'product_advertisement';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function planTypes(): array
    {
        return match ($this) {
            self::COMPANY_PROFILE => [
                PlanType::INDUSTRY->value,
            ],
            self::COLLABORATION_GROUPS => [
                PlanType::PREMIUM->value,
                PlanType::INDUSTRY->value,
            ],
            self::POSTS_ARTICLES_PHOTOS_VIDEOS => [
                PlanType::PREMIUM->value,
                PlanType::INDUSTRY->value,
            ],
            self::BUILD_NETWORK => [
                PlanType::PREMIUM->value,
                PlanType::INDUSTRY->value,
            ],
            self::DIRECT_MESSAGING => [PlanType::PREMIUM->value],
            self::CONNECT_ORGANIZATIONS => [
                PlanType::PREMIUM->value,
                PlanType::INDUSTRY->value,
            ],
            self::PRODUCT_ADVERTISEMENT => [
                PlanType::PREMIUM->value,
                PlanType::INDUSTRY->value,
            ],
            self::JOB_APPLICATIONS => [PlanType::PREMIUM->value],
            self::PROFILE_VIEWS_INSIGHTS => [
                PlanType::PREMIUM->value,
                PlanType::INDUSTRY->value,
            ],
        };
    }

    public static function labels(): array
    {
        return [
            self::COMPANY_PROFILE->value => 'Company Profile',
            self::BUILD_NETWORK->value => 'Build Your Network',
            self::COLLABORATION_GROUPS->value => 'Join Collaboration Groups',
            self::DIRECT_MESSAGING->value => 'Direct Messaging',
            self::POSTS_ARTICLES_PHOTOS_VIDEOS->value => 'Posts, Articles, Photos, Videos',
            self::PROFILE_VIEWS_INSIGHTS->value => 'Profile Views & Insights',
            self::JOB_APPLICATIONS->value => 'Job Applications (Submit CV/Résumé)',
            self::CONNECT_ORGANIZATIONS->value => 'Connect with Organizations',
            self::PRODUCT_ADVERTISEMENT->value => 'Product Advertisement',
        ];
    }
}

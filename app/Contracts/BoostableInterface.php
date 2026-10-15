<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface BoostableInterface
{
    public function campaigns(): MorphMany;

    public function activeCampaign(): MorphOne;

    public function isBoosted(): bool;

    public function getAdTitle(): string;

    public function getAdDescription(): ?string;

    public function getAdImageUrl(): ?string;

    public function getAdDestinationUrl(): ?string;

    public function getAdNetworkType(): string;

    public function getAdType(): string;
}

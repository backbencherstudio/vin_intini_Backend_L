<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    public function toArray($request)
    {
        $universitiesCount = (int) ($this->universities_count ?? 0);
        $residenciesCount = (int) ($this->residencies_count ?? 0);
        $facilitiesCount = (int) ($this->facilities_count ?? 0);
        $jobsCount = (int) ($this->jobs_count ?? 0);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'slug' => $this->slug,
            'total_resources' => $universitiesCount + $residenciesCount + $facilitiesCount + $jobsCount,
            'universities' => $this->whenLoaded('universities'),
            'residencies' => $this->whenLoaded('residencies'),
            'facilities' => $this->whenLoaded('facilities'),
            'jobs' => $this->whenLoaded('jobs'),
        ];
    }
}

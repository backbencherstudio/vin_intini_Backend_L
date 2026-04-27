<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'slug' => $this->slug,
            'total_resources' => (int) ($this->universities_count + $this->residencies_count + $this->facilities_count + $this->jobs_count),
            'universities' => $this->whenLoaded('universities'),
            'residencies' => $this->whenLoaded('residencies'),
            'facilities' => $this->whenLoaded('facilities'),
            'jobs' => $this->whenLoaded('jobs'),
        ];
    }
}

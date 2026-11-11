<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Concerns;

trait NormalizesPostBaccalaureateFields
{
    /**
     * Normalize trimmed string values and ensure external websites include a scheme.
     */
    protected function preparePostBaccalaureateProgramInput(): void
    {
        $updates = [];

        foreach (['program_name', 'location', 'phone', 'latitude', 'longitude'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $trimmed = trim($this->input($field));
                $updates[$field] = $trimmed === '' ? null : $trimmed;
            }
        }

        if ($this->has('website') && is_string($this->input('website'))) {
            $website = trim($this->input('website'));

            if ($website === '') {
                $updates['website'] = null;
            } else {
                if (! preg_match('~^[a-zA-Z][a-zA-Z0-9+.-]*://~', $website)) {
                    $website = 'https://'.$website;
                }

                $updates['website'] = $website;
            }
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }
}

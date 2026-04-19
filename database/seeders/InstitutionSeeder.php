<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JsonException;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedFilePath = database_path('seeders/data/us_universities_with_states.json');

        if (! File::exists($seedFilePath)) {
            throw new \RuntimeException("Institution seed file not found: {$seedFilePath}");
        }

        try {
            /** @var array<int, array<string, mixed>> $raw */
            $raw = json_decode(File::get($seedFilePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException("Invalid institution seed JSON: {$seedFilePath}", 0, $exception);
        }

        $now = now();

        /** @var array<int, array{name: string, country: string|null, website: string|null, created_at: \DateTimeInterface, updated_at: \DateTimeInterface}> $institutions */
        $institutions = Collection::make($raw)
            ->map(function (array $institution) use ($now): ?array {
                $name = trim((string) ($institution['name'] ?? ''));
                if ($name === '') {
                    return null;
                }

                $state = isset($institution['state'])
                    ? trim((string) $institution['state'])
                    : null;
                $state = $state === '' ? null : $state;

                $country = isset($institution['country'])
                    ? trim((string) $institution['country'])
                    : null;
                $country = $country === '' ? null : $country;

                return [
                    'name' => $name,
                    'state' => $state,
                    'country' => $country,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->filter()
            ->unique('name')
            ->values()
            ->all();

        foreach (array_chunk($institutions, 500) as $chunk) {
            DB::table('institutions')->upsert(
                $chunk,
                ['name'],
                ['state', 'country', 'updated_at'],
            );
        }
    }
}

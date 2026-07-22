<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class HelpCatalog
{
    protected ?array $catalog = null;

    public function version(): string
    {
        return (string) ($this->load()['catalog_version'] ?? 'unknown');
    }

    public function raw(): array
    {
        return $this->load();
    }

    public function forContext(string $panel, ?User $user = null, ?Mosque $mosque = null): Collection
    {
        if (! config('diwan.guidance.enabled')) {
            return collect();
        }

        $role = $this->roleFor($panel, $user, $mosque);

        return collect($this->load()['guides'] ?? [])
            ->filter(fn (array $guide): bool => ($guide['panel'] ?? null) === $panel)
            ->filter(fn (array $guide): bool => in_array($role, $guide['roles'] ?? [], true))
            ->filter(function (array $guide) use ($panel, $user, $mosque): bool {
                $permissions = $guide['permissions_any'] ?? [];
                if ($permissions === [] || $panel !== 'app' || ! $user || ! $mosque) {
                    return true;
                }

                return collect($permissions)->contains(fn (string $permission): bool => $user->canIn($mosque, $permission));
            })
            ->map(fn (array $guide): array => $this->hydrateRoute($guide, $mosque))
            ->values();
    }

    public function findVisible(string $id, string $panel, ?User $user = null, ?Mosque $mosque = null): ?array
    {
        return $this->forContext($panel, $user, $mosque)->firstWhere('id', $id);
    }

    public function search(string $query, string $panel, ?User $user = null, ?Mosque $mosque = null, int $limit = 12): Collection
    {
        $needle = $this->normalise($query);
        $guides = $this->forContext($panel, $user, $mosque);

        if ($needle === '') {
            return $guides->take($limit)->values();
        }

        $tokens = collect(preg_split('/\s+/', $needle) ?: [])->filter(fn (string $token) => mb_strlen($token) >= 2)->values();

        return $guides
            ->map(function (array $guide) use ($needle, $tokens): array {
                $title = $this->normalise((string) ($guide['title'] ?? ''));
                $summary = $this->normalise((string) ($guide['summary'] ?? ''));
                $keywords = $this->normalise(collect($guide['keywords'] ?? [])->implode(' '));
                $body = trim("{$title} {$summary} {$keywords}");
                $score = 0;

                if ($title === $needle) {
                    $score += 100;
                }
                if (str_contains($title, $needle)) {
                    $score += 60;
                }
                if (str_contains($body, $needle)) {
                    $score += 35;
                }

                foreach ($tokens as $token) {
                    if (str_contains($title, $token)) {
                        $score += 12;
                    } elseif (str_contains($body, $token)) {
                        $score += 6;
                    } else {
                        $close = collect(preg_split('/\s+/', $body) ?: [])->contains(
                            fn (string $word): bool => abs(strlen($word) - strlen($token)) <= 2
                                && levenshtein($word, $token) <= (strlen($token) > 6 ? 2 : 1),
                        );
                        if ($close) {
                            $score += 3;
                        }
                    }
                }

                return [...$guide, '_score' => $score];
            })
            ->filter(fn (array $guide): bool => $guide['_score'] > 0)
            ->sortByDesc('_score')
            ->take($limit)
            ->map(function (array $guide): array {
                unset($guide['_score']);

                return $guide;
            })
            ->values();
    }

    public function currentGuide(string $path, string $panel, ?User $user = null, ?Mosque $mosque = null): ?array
    {
        $normalisedPath = '/'.trim($path, '/');

        return $this->forContext($panel, $user, $mosque)
            ->sortByDesc(fn (array $guide): int => strlen((string) $guide['route']))
            ->first(function (array $guide) use ($normalisedPath): bool {
                $route = '/'.trim((string) $guide['route'], '/');

                return $route === $normalisedPath
                    || ($route !== '/' && str_starts_with($normalisedPath.'/', $route.'/'));
            });
    }

    public function validate(): array
    {
        $errors = [];
        $ids = [];
        $validPanels = ['public', 'app', 'admin'];
        $validRoles = [...config('roles.list', []), 'public', 'superadmin'];
        $validPermissions = config('roles.permissions', []);
        $imageBase = realpath(base_path('Manual Penguna'));

        foreach ($this->load()['guides'] ?? [] as $index => $guide) {
            foreach (['id', 'version', 'panel', 'title', 'summary', 'route', 'roles', 'prerequisites', 'outcome', 'steps', 'troubleshooting', 'keywords', 'images'] as $field) {
                if (! array_key_exists($field, $guide)) {
                    $errors[] = "Guide #{$index} tiada medan {$field}.";
                }
            }
            if (isset($guide['id']) && in_array($guide['id'], $ids, true)) {
                $errors[] = "ID guide berulang: {$guide['id']}.";
            }
            $ids[] = $guide['id'] ?? null;
            if (! in_array($guide['panel'] ?? null, $validPanels, true)) {
                $errors[] = ($guide['id'] ?? "#{$index}").' mempunyai panel tidak sah.';
            }
            if (($guide['roles'] ?? []) === [] || array_diff($guide['roles'] ?? [], $validRoles) !== []) {
                $errors[] = ($guide['id'] ?? "#{$index}").' mempunyai role tidak sah atau kosong.';
            }
            if (array_diff($guide['permissions_any'] ?? [], $validPermissions) !== []) {
                $errors[] = ($guide['id'] ?? "#{$index}").' merujuk permission yang tidak wujud.';
            }
            if (! str_starts_with((string) ($guide['route'] ?? ''), '/')) {
                $errors[] = ($guide['id'] ?? "#{$index}").' mempunyai route tidak sah.';
            }
            foreach ($guide['images'] ?? [] as $image) {
                $path = realpath(base_path((string) $image));
                if (! $imageBase || ! $path || ! str_starts_with(strtolower($path), strtolower($imageBase.DIRECTORY_SEPARATOR)) || ! str_ends_with(strtolower($path), '.png')) {
                    $errors[] = ($guide['id'] ?? "#{$index}")." mempunyai rujukan imej tidak sah: {$image}.";
                }
            }
            foreach ($guide['steps'] ?? [] as $stepIndex => $step) {
                foreach (['id', 'title', 'instruction', 'target'] as $field) {
                    if (! filled($step[$field] ?? null)) {
                        $errors[] = ($guide['id'] ?? "#{$index}")." langkah {$stepIndex} tiada {$field}.";
                    }
                }
                if (filled($step['route'] ?? null) && ! str_starts_with((string) $step['route'], '/')) {
                    $errors[] = ($guide['id'] ?? "#{$index}")." langkah {$stepIndex} mempunyai route tidak sah.";
                }
                if (filled($step['target'] ?? null) && ! preg_match('/^[a-z0-9][a-z0-9._-]*$/', (string) $step['target'])) {
                    $errors[] = ($guide['id'] ?? "#{$index}")." langkah {$stepIndex} mempunyai sasaran UI tidak stabil.";
                }
            }
        }

        return $errors;
    }

    protected function roleFor(string $panel, ?User $user, ?Mosque $mosque): string
    {
        return match ($panel) {
            'public' => 'public',
            'admin' => $user?->is_superadmin ? 'superadmin' : 'unauthorised',
            default => $user && $mosque ? ($user->roleIn($mosque) ?? ($user->is_superadmin ? 'superadmin' : 'unauthorised')) : 'unauthorised',
        };
    }

    protected function hydrateRoute(array $guide, ?Mosque $mosque): array
    {
        $guide['route'] = str_replace('{tenant}', $mosque?->slug ?? '{tenant}', (string) $guide['route']);
        $guide['steps'] = collect($guide['steps'] ?? [])->map(function (array $step) use ($mosque): array {
            if (filled($step['route'] ?? null)) {
                $step['route'] = str_replace('{tenant}', $mosque?->slug ?? '{tenant}', (string) $step['route']);
            }

            return $step;
        })->all();

        return $guide;
    }

    protected function normalise(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', Str::lower(Str::ascii($value))));
    }

    protected function load(): array
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        $path = (string) config('diwan.guidance.catalog_path');
        if (! is_file($path)) {
            throw new RuntimeException("Katalog bantuan tidak ditemui: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return $this->catalog = is_array($decoded) ? $decoded : [];
    }
}

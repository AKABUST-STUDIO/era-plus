<?php

declare(strict_types=1);

namespace App\Services;

use App\Filament\Contracts\HasOrganizationPermissions;
use App\Filament\Contracts\HasProjectPermissions;
use BackedEnum;
use Filament\Facades\Filament;
use Illuminate\Support\Str;

class PermissionRegistry
{
    public const SCOPE_ORGANIZATION = 'organization';

    public const SCOPE_PROJECT = 'project';

    /**
     * @var array<string, list<string>>
     */
    private const SCOPE_PANELS = [
        self::SCOPE_ORGANIZATION => ['organization', 'organization.settings'],
        self::SCOPE_PROJECT => ['project', 'project.settings'],
    ];

    /**
     * @var array<string, string>
     */
    private const SCOPE_MARKERS = [
        self::SCOPE_ORGANIZATION => HasOrganizationPermissions::class,
        self::SCOPE_PROJECT => HasProjectPermissions::class,
    ];

    /**
     * @var array<string, array<string, list<string>>>|null
     */
    private static ?array $cache = null;

    /**
     * @return array<string, list<string>>
     */
    public static function grouped(string $scope = self::SCOPE_ORGANIZATION): array
    {
        if (self::$cache !== null && array_key_exists($scope, self::$cache)) {
            return self::$cache[$scope];
        }

        $marker = self::SCOPE_MARKERS[$scope] ?? null;
        $groups = [];

        if ($marker !== null) {
            foreach (self::SCOPE_PANELS[$scope] ?? [] as $panelId) {
                $panel = Filament::getPanel($panelId, isStrict: false);

                if ($panel === null) {
                    continue;
                }

                $candidates = [
                    ...array_values($panel->getResources()),
                    ...array_values($panel->getPages()),
                ];

                foreach ($candidates as $class) {
                    if (! is_subclass_of($class, $marker)) {
                        continue;
                    }

                    $enumClass = self::resolveEnum($class);

                    if ($enumClass === null) {
                        continue;
                    }

                    $groups[self::resolveResourceKey($enumClass)] = array_map(
                        static fn (BackedEnum $case): string => (string) $case->value,
                        $enumClass::cases(),
                    );
                }
            }
        }

        self::$cache ??= [];
        self::$cache[$scope] = $groups;

        return $groups;
    }

    /**
     * @return list<string>
     */
    public static function granular(string $scope = self::SCOPE_ORGANIZATION): array
    {
        $names = [];

        foreach (self::grouped($scope) as $permissions) {
            array_push($names, ...$permissions);
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            ...self::granular(self::SCOPE_ORGANIZATION),
            ...self::granular(self::SCOPE_PROJECT),
        ];
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    /**
     * @return class-string<BackedEnum>|null
     */
    private static function resolveEnum(string $class): ?string
    {
        if (method_exists($class, 'getPermissionEnum')) {
            return $class::getPermissionEnum();
        }

        if (method_exists($class, 'getModel')) {
            $model = $class::getModel();

            if (is_string($model) && $model !== '') {
                $candidate = 'App\\Enums\\Permissions\\'.class_basename($model).'Permission';

                if (enum_exists($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private static function resolveResourceKey(string $enumClass): string
    {
        $basename = class_basename($enumClass);
        $trimmed = str_ends_with($basename, 'Permission')
            ? substr($basename, 0, -strlen('Permission'))
            : $basename;

        return Str::snake($trimmed);
    }
}

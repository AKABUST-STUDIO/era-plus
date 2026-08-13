<?php

declare(strict_types=1);

namespace App\Services;

class PermissionRegistry
{
    /**
     * @return list<string>
     */
    public static function resources(): array
    {
        return [
            'project',
            'organization_user',
            'role',
            'organization',
            'billing',
            'invoice',
            'activity',
        ];
    }

    /**
     * @return list<string>
     */
    public static function actions(?string $resource = null): array
    {
        return match ($resource) {
            'organization' => ['update', 'delete'],
            'project' => ['view_any', 'create', 'update_any', 'delete_any'],
            'billing' => ['view', 'update'],
            'invoice', 'activity' => ['view'],
            default => [
                'view_any',
                'view',
                'create',
                'update',
                'update_any',
                'delete',
                'delete_any',
            ],
        };
    }

    /**
     * @return list<string>
     */
    public static function legacy(): array
    {
        return [
            ProjectAccess::ABILITY_ADMINISTER_ORGANIZATION,
            ProjectAccess::ABILITY_ADMINISTER_PROJECT,
            ProjectAccess::ABILITY_MANAGE_MEMBERS,
            ProjectAccess::ABILITY_MANAGE_PARTICIPANTS,
            ProjectAccess::ABILITY_MANAGE_FINANCE,
            ProjectAccess::ABILITY_VIEW_ALL_TRAVEL_EXPENSES,
            ProjectAccess::ABILITY_MANAGE_COUNTRY_LIMITS,
            ProjectAccess::ABILITY_MANAGE_SETTINGS,
        ];
    }

    /**
     * @return list<string>
     */
    public static function granular(): array
    {
        $names = [];

        foreach (self::resources() as $resource) {
            foreach (self::actions($resource) as $action) {
                $names[] = "{$action}_{$resource}";
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [...self::legacy(), ...self::granular()];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::resources() as $resource) {
            $groups[$resource] = array_map(
                static fn (string $action): string => "{$action}_{$resource}",
                self::actions($resource),
            );
        }

        return $groups;
    }
}

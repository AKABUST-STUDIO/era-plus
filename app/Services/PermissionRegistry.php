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
            'member',
            'role',
            'setting',
        ];
    }

    /**
     * @return list<string>
     */
    public static function actions(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'update_any',
            'delete',
            'delete_any',
        ];
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
            foreach (self::actions() as $action) {
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
                self::actions(),
            );
        }

        return $groups;
    }
}

<?php

namespace App\Presenters;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class ActivityLogPresenter
{
    /**
     * @return array{
     *     avatar_url: ?string,
     *     initials: string,
     *     description: HtmlString,
     *     when: string,
     * }
     */
    public static function present(ActivityLog $log, string $whenFormat = 'diff'): array
    {
        return [
            'avatar_url' => self::avatarUrl($log),
            'initials' => self::initials($log),
            'description' => self::fluent($log),
            'when' => self::when($log, $whenFormat),
        ];
    }

    public static function fluent(ActivityLog $log): HtmlString
    {
        $causer = $log->causer;
        $actor = $causer instanceof User && $causer->getKey() === auth()->id()
            ? __('activity.you')
            : ($causer?->name ?? __('activity.system'));

        $raw = (string) $log->description;
        $description = $raw;
        $properties = $log->properties instanceof Collection
            ? $log->properties->all()
            : (array) $log->properties;

        foreach ($properties as $key => $value) {
            if (is_scalar($value) && str_contains($description, ':'.$key)) {
                $description = str_replace(
                    ':'.$key,
                    '<strong class="font-semibold text-gray-950 dark:text-white">'.e((string) $value).'</strong>',
                    $description,
                );
            }
        }

        if ($description === $raw) {
            $description = e($raw);
            $subject = $log->subject;

            if (filled($log->subject_type)) {
                $type = class_basename((string) $log->subject_type);
                $name = $subject?->name
                    ?? $subject?->title
                    ?? $subject?->slug
                    ?? ($log->subject_id !== null ? '#'.$log->subject_id : null);

                $description .= $name === null
                    ? ' <strong class="font-semibold text-gray-950 dark:text-white">'.e($type).'</strong>'
                    : ' '.e($type).' <strong class="font-semibold text-gray-950 dark:text-white">'.e((string) $name).'</strong>';
            }
        }

        return new HtmlString('<span>'.e($actor).'</span> '.$description);
    }

    public static function avatarUrl(ActivityLog $log): ?string
    {
        $causer = $log->causer;

        if ($causer !== null && method_exists($causer, 'avatarUrl')) {
            return (string) $causer->avatarUrl();
        }

        return null;
    }

    public static function initials(ActivityLog $log): string
    {
        $causer = $log->causer;
        $name = $causer instanceof User ? $causer->name : null;

        return initials($name, 'SY');
    }

    private static function when(ActivityLog $log, string $format): string
    {
        $createdAt = $log->created_at;

        if ($createdAt === null) {
            return '';
        }

        return $format === 'date'
            ? $createdAt->translatedFormat('M j')
            : $createdAt->diffForHumans();
    }
}

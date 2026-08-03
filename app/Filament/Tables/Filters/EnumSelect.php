<?php

namespace App\Filament\Tables\Filters;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

class EnumSelect
{
    public const ANY = 'any';

    /**
     * @param  class-string<HasLabel&BackedEnum>  $enum
     */
    public static function make(string $name, string $enum, string $anyLabel, ?string $anyIcon = null): Select
    {
        $options = [self::ANY => self::option($anyLabel, $anyIcon)];

        foreach ($enum::cases() as $case) {
            $options[$case->value] = self::option(
                $case->getLabel() ?? $case->name,
                $case instanceof HasIcon ? $case->getIcon() : null,
            );
        }

        return Select::make($name)
            ->hiddenLabel()
            ->validationAttribute($anyLabel)
            ->options($options)
            ->allowHtml()
            ->default(self::ANY)
            ->selectablePlaceholder(false)
            ->native(false)
            ->live();
    }

    public static function applies(mixed $value): bool
    {
        return filled($value) && $value !== self::ANY;
    }

    private static function option(string $label, ?string $icon): string
    {
        return view('filament.forms.components.enum-option', [
            'icon' => $icon,
            'label' => $label,
        ])->render();
    }
}

<?php

namespace App\Filament\Panels\Spotlight;

use pxlrbt\FilamentSpotlight\Commands\PageCommand as BasePageCommand;

class PageCommand extends BasePageCommand
{
    public function __construct(
        string $name,
        string $url,
        protected string $panelId,
        protected string $kind = 'page',
    ) {
        parent::__construct($name, $url);
    }

    public function getPanelId(): string
    {
        return $this->panelId;
    }

    public function getKind(): string
    {
        return $this->kind;
    }
}

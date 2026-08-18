<?php

namespace App\Filament\Panels\Spotlight;

use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use pxlrbt\FilamentSpotlight\Commands\ResourceCommand as BaseResourceCommand;

class ResourceCommand extends BaseResourceCommand
{
    public function __construct(
        string $resource,
        string $page,
        string $key,
        protected string $panelId,
        protected ?Model $tenant = null,
    ) {
        parent::__construct($resource, $page, $key);
    }

    public function getId(): string
    {
        return md5($this->resource::class.$this->page::class.$this->panelId);
    }

    public function getName(): string
    {
        return PanelBreadcrumb::prefix($this->panelId, parent::getName());
    }

    public function getUrl(null|int|string $recordKey): string
    {
        $parameters = PanelContext::routeDefaults($this->tenant);

        if ($recordKey) {
            $parameters['record'] = $recordKey;
        }

        return $this->resource::getUrl(
            $this->key,
            $parameters,
            panel: $this->panelId,
            tenant: $this->tenant,
        );
    }

    public function shouldBeShown(): bool
    {
        return PanelContext::run($this->panelId, $this->tenant, fn (): bool => parent::shouldBeShown());
    }

    public function searchRecord($query): EloquentCollection|Collection|array
    {
        return PanelContext::run($this->panelId, $this->tenant, fn () => parent::searchRecord($query));
    }

    public function getPanelId(): string
    {
        return $this->panelId;
    }

    public function getKind(): string
    {
        return match (true) {
            $this->page instanceof CreateRecord => 'create',
            $this->page instanceof EditRecord => 'edit',
            $this->page instanceof ViewRecord => 'view',
            $this->page instanceof ManageRelatedRecords => 'view',
            default => 'page',
        };
    }
}

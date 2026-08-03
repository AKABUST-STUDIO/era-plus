<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class CheckToggle extends Field
{
    protected string $view = 'filament.forms.components.check-toggle';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(false);
        $this->hiddenLabel();
        $this->rule('boolean');
    }

    public function getStateExpression(): string
    {
        return '$wire.'.$this->applyStateBindingModifiers("\$entangle('{$this->getStatePath()}')");
    }
}

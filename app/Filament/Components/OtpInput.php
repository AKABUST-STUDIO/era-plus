<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\Contracts;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;

class OtpInput extends Field implements Contracts\CanBeLengthConstrained
{
    use Concerns\CanBeAutocapitalized;
    use Concerns\CanBeAutocompleted;
    use Concerns\CanBeLengthConstrained;
    use Concerns\CanBeReadOnly;
    use Concerns\HasAffixes;
    use Concerns\HasExtraInputAttributes;
    use HasExtraAlpineAttributes;

    protected string $view = 'components.otp-input.component';

    protected int|\Closure|null $numberInput = 4;

    protected bool|\Closure|null $isRtl = false;

    protected string|\Closure|null $type = 'number';

    protected string|\Closure|null $submitAction = null;

    public function submitAction(string|\Closure|null $action): static
    {
        $this->submitAction = $action;

        return $this;
    }

    public function getSubmitAction(): ?string
    {
        return $this->evaluate($this->submitAction);
    }

    public function numberInput(int|\Closure $number = 4): static
    {
        $this->numberInput = $number;

        return $this;
    }

    public function getNumberInput(): int
    {
        return $this->evaluate($this->numberInput);
    }

    public function password(): static
    {
        $this->type = 'password';

        return $this;
    }

    public function text(): static
    {
        $this->type = 'text';

        return $this;
    }

    public function getType(): string
    {
        return $this->evaluate($this->type);
    }

    public function rtl(bool|\Closure $condition = false): static
    {
        $this->isRtl = $condition;

        return $this;
    }

    public function getInputsContainerDirection(): string
    {
        return $this->evaluate($this->isRtl);
    }
}

<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;

class ProjectDeleting
{
    use Dispatchable;

    public function __construct(public readonly Project $project) {}
}

<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary;

use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class SignedUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        $lifetime = (int) config('media-library.temporary_url_default_lifetime', 60);

        return $this->getTemporaryUrl(Carbon::now()->addMinutes($lifetime));
    }
}

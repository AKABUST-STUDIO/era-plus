<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DuskTestCase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        $this->withoutVite();
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

pest()->extend(DuskTestCase::class)
    ->in('Browser');

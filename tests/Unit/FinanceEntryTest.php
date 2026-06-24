<?php

namespace Tests\Unit;

use App\Enums\FinanceOperation;
use App\Models\FinanceEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_operation_casts_to_enum(): void
    {
        $entry = FinanceEntry::factory()->add()->create(['amount' => 100]);

        $this->assertSame(FinanceOperation::Add, $entry->fresh()->operation);
    }

    public function test_signed_amount_is_positive_for_add(): void
    {
        $entry = FinanceEntry::factory()->add()->create(['amount' => 123.45]);

        $this->assertSame('123.45', $entry->signedAmount());
    }

    public function test_signed_amount_is_negative_for_subtract(): void
    {
        $entry = FinanceEntry::factory()->subtract()->create(['amount' => 50]);

        $this->assertSame('-50.00', $entry->signedAmount());
    }

    public function test_occurred_at_is_cast_to_date(): void
    {
        $entry = FinanceEntry::factory()->create(['occurred_at' => '2026-05-01']);

        $this->assertSame('2026-05-01', $entry->fresh()->occurred_at->toDateString());
    }
}

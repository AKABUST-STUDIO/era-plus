<?php

namespace Tests\Unit\Jobs;

use App\Ai\Agents\ExtractJourneyDetails;
use App\Ai\Agents\ExtractPaymentDetails;
use App\Enums\Project\TravelExpense\AiExtractionTarget;
use App\Jobs\RunTravelExpenseExtraction;
use App\Models\Project\TravelExpenseAiExtraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class RunTravelExpenseExtractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_job_is_queued_on_the_dedicated_ai_extraction_queue(): void
    {
        $job = new RunTravelExpenseExtraction(1);

        $this->assertSame('ai-extraction', $job->queue);
    }

    public function test_journey_extraction_stores_agent_output_and_completed_at(): void
    {
        ExtractJourneyDetails::fake([[
            'date' => '2026-05-01',
            'from' => 'Madrid',
            'to' => 'Berlin',
            'travel_type' => 'departure',
            'transportation_type' => 'flight',
        ]]);

        $extraction = TravelExpenseAiExtraction::factory()->create([
            'target' => AiExtractionTarget::Journey,
            'file_paths' => [$this->fakeImagePath('boarding.png')],
        ]);

        (new RunTravelExpenseExtraction($extraction->id))->handle();

        $extraction->refresh();

        $this->assertSame('completed', $extraction->status);
        $this->assertNotNull($extraction->completed_at);
        $this->assertNull($extraction->failed_at);
        $this->assertSame('Madrid', $extraction->extracted_data['from']);
        $this->assertSame('flight', $extraction->extracted_data['transportation_type']);
    }

    public function test_payment_extraction_uses_the_payment_agent(): void
    {
        ExtractPaymentDetails::fake([[
            'cost' => 45.9,
            'currency' => 'EUR',
        ]]);

        $extraction = TravelExpenseAiExtraction::factory()->create([
            'target' => AiExtractionTarget::Payment,
            'file_paths' => [$this->fakeImagePath('receipt.jpg')],
        ]);

        (new RunTravelExpenseExtraction($extraction->id))->handle();

        $extraction->refresh();

        $this->assertSame('completed', $extraction->status);
        $this->assertSame(45.9, $extraction->extracted_data['cost']);
        $this->assertSame('EUR', $extraction->extracted_data['currency']);
    }

    public function test_null_fields_are_stripped_from_the_extracted_data(): void
    {
        ExtractJourneyDetails::fake([[
            'date' => '2026-05-01',
            'from' => 'Madrid',
            'to' => null,
            'travel_type' => null,
            'transportation_type' => 'train',
        ]]);

        $extraction = TravelExpenseAiExtraction::factory()->create([
            'target' => AiExtractionTarget::Journey,
            'file_paths' => [$this->fakeImagePath('ticket.png')],
        ]);

        (new RunTravelExpenseExtraction($extraction->id))->handle();

        $extraction->refresh();

        $this->assertArrayNotHasKey('to', $extraction->extracted_data);
        $this->assertArrayNotHasKey('travel_type', $extraction->extracted_data);
        $this->assertSame('Madrid', $extraction->extracted_data['from']);
    }

    public function test_missing_files_mark_the_extraction_as_failed(): void
    {
        ExtractJourneyDetails::fake([[]]);

        $extraction = TravelExpenseAiExtraction::factory()->create([
            'target' => AiExtractionTarget::Journey,
            'file_paths' => ['/tmp/does-not-exist.png'],
        ]);

        (new RunTravelExpenseExtraction($extraction->id))->handle();

        $extraction->refresh();

        $this->assertSame('failed', $extraction->status);
        $this->assertNotNull($extraction->failed_at);
        $this->assertNull($extraction->completed_at);
        $this->assertStringContainsString('No readable files', (string) $extraction->error_message);
    }

    public function test_agent_exceptions_mark_the_extraction_as_failed(): void
    {
        ExtractJourneyDetails::fake(function () {
            throw new RuntimeException('provider unavailable');
        });

        $extraction = TravelExpenseAiExtraction::factory()->create([
            'target' => AiExtractionTarget::Journey,
            'file_paths' => [$this->fakeImagePath('ticket.png')],
        ]);

        (new RunTravelExpenseExtraction($extraction->id))->handle();

        $extraction->refresh();

        $this->assertSame('failed', $extraction->status);
        $this->assertSame('provider unavailable', $extraction->error_message);
    }

    private function fakeImagePath(string $filename): string
    {
        $relative = 'ai-extractions/'.$filename;
        Storage::disk('local')->put($relative, "\x89PNG\r\n\x1a\n");

        return Storage::disk('local')->path($relative);
    }
}

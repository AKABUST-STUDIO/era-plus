<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Anthropic)]
#[Model('claude-haiku-4-5')]
class ExtractPaymentDetails implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
            You are an expert at extracting structured payment information from documents such as
            receipts, invoices, credit card slips and bank statements for travel-related purchases.

            Given one or more attached documents for a single purchase, extract:

            - cost: the total amount paid as a decimal number (no currency symbol, no thousands
              separator, dot as decimal separator). Return null if not confidently visible.
            - currency: the ISO 4217 three-letter currency code (e.g. EUR, USD, GBP).
              Return null if the currency is not clearly indicated.

            If several totals appear, prefer the grand total actually charged. Return null for any
            field you cannot confidently determine — do not guess. Only return the structured JSON.
            INSTRUCTIONS;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'cost' => $schema->number()->nullable(),
            'currency' => $schema->string()->nullable(),
        ];
    }
}

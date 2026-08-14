<?php

namespace Tests\Concerns;

use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

/**
 * Intercepts the Stripe SDK at its HTTP seam so billing code runs its real
 * production path in tests without reaching the network.
 */
trait FakesStripe
{
    protected function setUpFakesStripe(): void
    {
        ApiRequestor::setHttpClient(new class implements ClientInterface
        {
            /**
             * @param  array<string, mixed>  $headers
             * @param  array<string, mixed>  $params
             * @return array{0: string, 1: int, 2: array<string, mixed>}
             */
            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
            {
                return [json_encode($this->responseFor($absUrl, $params)), 200, []];
            }

            /**
             * @param  array<string, mixed>  $params
             * @return array<string, mixed>
             */
            private function responseFor(string $url, array $params): array
            {
                if (str_contains($url, '/v1/subscriptions')) {
                    return $this->subscription($params);
                }

                return $this->customer();
            }

            /**
             * @return array<string, mixed>
             */
            private function customer(): array
            {
                return [
                    'id' => 'cus_'.uniqid(),
                    'object' => 'customer',
                ];
            }

            /**
             * @param  array<string, mixed>  $params
             * @return array<string, mixed>
             */
            private function subscription(array $params): array
            {
                $items = $params['items'] ?? [['price' => 'price_fake', 'quantity' => 1]];

                $data = [];

                foreach ($items as $item) {
                    $data[] = [
                        'id' => 'si_'.uniqid(),
                        'object' => 'subscription_item',
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => [
                            'id' => $item['price'] ?? 'price_fake',
                            'object' => 'price',
                            'product' => 'prod_'.uniqid(),
                            'recurring' => ['interval' => 'month'],
                        ],
                    ];
                }

                return [
                    'id' => 'sub_'.uniqid(),
                    'object' => 'subscription',
                    'status' => 'active',
                    'customer' => $params['customer'] ?? 'cus_fake',
                    'items' => [
                        'object' => 'list',
                        'has_more' => false,
                        'url' => '/v1/subscription_items',
                        'data' => $data,
                    ],
                ];
            }
        });
    }

    protected function tearDownFakesStripe(): void
    {
        ApiRequestor::setHttpClient(null);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ProvisioningOrder;
use App\Models\Quote;
use App\Models\QuoteItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CorporateDemoSeeder extends Seeder
{
    /** @var non-empty-list<string> */
    private const QUOTE_NOTE_SNIPPETS = [
        'Pricing excludes handset freight.',
        'Corporate desk review required above 50 extensions.',
        'Valid for standard business hours install only.',
    ];

    /** @var non-empty-list<string> */
    private const PROVISIONING_NOTE_SNIPPETS = [
        'Site contact to confirm access badge.',
        'Cutover window agreed with customer PM.',
        'Depends on carrier port completion.',
    ];

    public function run(): void
    {
        $quoteTarget = max(40, (int) env('UC_DEMO_QUOTES', 120));
        $provTarget = max(40, (int) env('UC_DEMO_PROVISIONING', 180));

        $customerIds = Account::query()->where('tier', 'customer')->pluck('id')->all();
        if ($customerIds === []) {
            return;
        }

        $this->seedQuotes($customerIds, $quoteTarget);
        $this->seedProvisioning($customerIds, $provTarget);
        $this->seedShowcaseCorporate();
    }

    /**
     * @param  list<int>  $customerIds
     */
    private function seedQuotes(array $customerIds, int $quoteTarget): void
    {
        $statuses = ['draft', 'draft', 'submitted', 'submitted', 'under_review', 'approved', 'approved', 'rejected', 'expired'];
        $titles = [
            'Corporate SIP trunk — 50 concurrent',
            'Hosted PBX — 120 extensions',
            'Unified comms bundle — 3 sites',
            'Contact centre seats — Q2 renewal',
            'NBN + voice failover package',
            'Executive mobile fleet — 45 lines',
        ];
        $products = [
            ['SIP-TRK-50', 'SIP trunk 50 channel', 890.00],
            ['PBX-EXT', 'Hosted PBX extension', 18.50],
            ['DID-GEO', 'Geographic DID monthly', 6.00],
            ['CC-SEAT', 'Contact centre seat', 65.00],
            ['NBN-100', 'Business NBN 100/40', 120.00],
        ];

        for ($i = 0; $i < $quoteTarget; $i++) {
            $accountId = $customerIds[array_rand($customerIds)];
            $status = $statuses[array_rand($statuses)];
            $created = Carbon::now()->subDays(random_int(5, 400));
            $validUntil = (clone $created)->addDays(random_int(14, 60));

            $quote = Quote::create([
                'reference' => $this->uniqueRef('BQ'),
                'account_id' => $accountId,
                'title' => $titles[array_rand($titles)],
                'status' => $status,
                'subtotal_aud' => 0,
                'valid_until' => $validUntil,
                'notes' => DemoSeedRandom::maybePick(0.4, self::QUOTE_NOTE_SNIPPETS),
                'created_at' => $created,
                'updated_at' => $created->copy()->addDays(random_int(0, 10)),
            ]);

            $lineCount = random_int(2, 5);
            $subtotal = 0;
            for ($n = 0; $n < $lineCount; $n++) {
                [$code, $desc, $unit] = $products[array_rand($products)];
                $qty = random_int(1, 120);
                $lineTotal = round($qty * $unit, 2);
                $subtotal += $lineTotal;
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_code' => $code,
                    'description' => $desc,
                    'quantity' => $qty,
                    'unit_price_aud' => $unit,
                    'line_total_aud' => $lineTotal,
                ]);
            }

            $quote->update(['subtotal_aud' => round($subtotal, 2)]);
        }
    }

    /**
     * @param  list<int>  $customerIds
     */
    private function seedProvisioning(array $customerIds, int $provTarget): void
    {
        $statuses = ['submitted', 'scheduled', 'in_progress', 'in_progress', 'completed', 'completed', 'cancelled'];
        $titles = [
            'Install SIP trunk — primary site',
            'Cutover weekend — PBX migration',
            'Add DIDs to auto attendant',
            'Provision hunt group — sales',
            'Decommission legacy ISDN',
        ];

        $approvedQuotes = Quote::query()->where('status', 'approved')->get();

        for ($i = 0; $i < $provTarget; $i++) {
            $accountId = $customerIds[array_rand($customerIds)];
            $status = $statuses[array_rand($statuses)];
            $created = Carbon::now()->subDays(random_int(0, 300));
            $quote = random_int(0, 3) === 0 && $approvedQuotes->isNotEmpty()
                ? $approvedQuotes->random()
                : null;

            if ($quote) {
                $accountId = $quote->account_id;
            }

            ProvisioningOrder::create([
                'reference' => $this->uniqueRef('PR'),
                'account_id' => $accountId,
                'quote_id' => $quote?->id,
                'title' => $titles[array_rand($titles)],
                'status' => $status,
                'requested_for' => $created->copy()->addDays(random_int(3, 21)),
                'completed_at' => $status === 'completed' ? $created->copy()->addDays(random_int(5, 30)) : null,
                'description' => DemoSeedRandom::maybePick(0.5, self::PROVISIONING_NOTE_SNIPPETS),
                'created_at' => $created,
                'updated_at' => $created->copy()->addDays(random_int(0, 14)),
            ]);
        }
    }

    private function seedShowcaseCorporate(): void
    {
        $showcase = Account::query()->where('name', config('demo.showcase_account_name'))->first()
            ?? Account::query()->where('tier', 'customer')->first();

        if (! $showcase) {
            return;
        }

        $quote = Quote::updateOrCreate(
            ['reference' => 'BQ-SHOW01'],
            [
                'account_id' => $showcase->id,
                'title' => 'Corporate voice refresh — 3 sites (bid)',
                'status' => 'approved',
                'subtotal_aud' => 18420.50,
                'valid_until' => now()->addDays(21),
                'notes' => 'Pricing approved by corporate desk — proceed to provisioning.',
                'updated_at' => now(),
            ]
        );

        QuoteItem::query()->where('quote_id', $quote->id)->delete();
        QuoteItem::create([
            'quote_id' => $quote->id,
            'product_code' => 'SIP-TRK-50',
            'description' => 'SIP trunk 50 channel — '.config('demo.showcase_suburb', 'Parramatta').' DC',
            'quantity' => 1,
            'unit_price_aud' => 890,
            'line_total_aud' => 890,
        ]);
        QuoteItem::create([
            'quote_id' => $quote->id,
            'product_code' => 'PBX-EXT',
            'description' => 'Hosted PBX extension',
            'quantity' => 95,
            'unit_price_aud' => 18.5,
            'line_total_aud' => 1757.5,
        ]);

        ProvisioningOrder::updateOrCreate(
            ['reference' => 'PR-SHOW01'],
            [
                'account_id' => $showcase->id,
                'quote_id' => $quote->id,
                'title' => 'Provision approved bid — Norman? Plaza cutover ('.config('demo.showcase_suburb', 'Parramatta').')',
                'status' => 'in_progress',
                'requested_for' => now()->addDays(5),
                'description' => 'Tracking install windows with corporate client PM.',
                'updated_at' => now(),
            ]
        );
    }

    private function uniqueRef(string $prefix): string
    {
        do {
            $reference = $prefix.'-'.strtoupper(Str::random(6));
        } while (
            Quote::where('reference', $reference)->exists()
            || ProvisioningOrder::where('reference', $reference)->exists()
        );

        return $reference;
    }
}

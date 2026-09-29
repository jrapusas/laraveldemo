<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ServiceLine;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UcDemoSeeder extends Seeder
{
    /** @var non-empty-list<string> */
    private const TICKET_DESCRIPTION_SNIPPETS = [
        'Customer reports issue started after last maintenance window.',
        'Logs attached from edge SBC; RTP flow looks asymmetric.',
        'Site contact available 9–5 AEST for test calls.',
        'Similar ticket closed last month — check known issues doc.',
        'Reseller escalated with priority customer flag.',
    ];

    /** @var list<int> */
    private array $lineIdsByAccount = [];

    public function run(): void
    {
        $accountTarget = max(12, (int) env('UC_DEMO_ACCOUNTS', 48));
        $ticketTarget = max(200, (int) env('UC_DEMO_TICKETS', 3600));

        DB::transaction(function () use ($accountTarget, $ticketTarget) {
            $this->seedAccountsAndLines($accountTarget);
            $this->seedBulkTickets($ticketTarget);
            $this->seedShowcaseTickets();
            $this->seedStatusHistoryBackfill();
            $this->call(CorporateDemoSeeder::class);
        });
    }

    private function seedAccountsAndLines(int $accountTarget): void
    {
        $resellerCount = max(6, (int) round($accountTarget * 0.2));
        $timezones = [
            'Australia/Sydney',
            'Australia/Melbourne',
            'Australia/Brisbane',
            'Australia/Perth',
            'Australia/Adelaide',
        ];

        $resellerNames = config('demo.reseller_names', []);
        $customerNames = config('demo.customer_names', []);

        if ($resellerNames === [] || $customerNames === []) {
            return;
        }

        $now = now();

        for ($i = 0; $i < $resellerCount; $i++) {
            $name = $resellerNames[$i % count($resellerNames)];
            if ($i >= count($resellerNames)) {
                $name .= ' '.($i + 1);
            }
            $account = Account::create([
                'name' => $name,
                'tier' => 'reseller',
                'email' => 'noc+'.($i + 1).'@'.str($name)->slug().'.example',
                'timezone' => $timezones[$i % count($timezones)],
                'created_at' => $now->copy()->subMonths(random_int(6, 36)),
                'updated_at' => $now,
            ]);
            $this->seedLinesForAccount($account, random_int(4, 12), true);
        }

        $customersToCreate = $accountTarget - $resellerCount;
        for ($i = 0; $i < $customersToCreate; $i++) {
            $name = $customerNames[$i % count($customerNames)];
            if ($i >= count($customerNames)) {
                $name .= ' '.($i + 1);
            }
            $isHospitality = str_contains(strtolower($name), 'flipper king')
                || str_contains(strtolower($name), 'groceteria')
                || str_contains(strtolower($name), 'budget barn')
                || str_contains(strtolower($name), 'kitten not lion')
                || str_contains(strtolower($name), 'timtammy')
                || str_contains(strtolower($name), 'tripped centre');
            $account = Account::create([
                'name' => $name,
                'tier' => 'customer',
                'email' => 'support+'.($i + 1).'@'.str($name)->slug().'.example',
                'timezone' => $timezones[$i % count($timezones)],
                'created_at' => $now->copy()->subMonths(random_int(3, 48)),
                'updated_at' => $now,
            ]);
            $this->seedLinesForAccount($account, random_int(2, 8), false, $isHospitality);
        }
    }

    private function seedLinesForAccount(Account $account, int $count, bool $isReseller, bool $hospitality = false): void
    {
        $products = $hospitality
            ? ['hosted_pbx', 'did', 'pos_terminal']
            : ['sip_trunk', 'hosted_pbx', 'did'];

        $statuses = ['active', 'active', 'active', 'degraded', 'provisioning'];

        for ($n = 0; $n < $count; $n++) {
            $product = $products[array_rand($products)];
            $did = $product === 'sip_trunk' ? null : '+612'.random_int(60000000, 89999999);
            $suburbs = config('demo.nsw_suburbs', ['Parramatta', 'Penrith', 'Newcastle']);
            $label = match ($product) {
                'sip_trunk' => 'SIP trunk — '.DemoSeedRandom::pick($suburbs).' DC',
                'hosted_pbx' => 'Hosted PBX — '.DemoSeedRandom::pick(['sales', 'support', 'after-hours']).' hunt group',
                'did' => DemoSeedRandom::pick(['Main reception', 'After hours', 'Fax line', 'IVR overflow']).' DID',
                'pos_terminal' => 'POS lane '.($n + 1).' — '.DemoSeedRandom::pick(['dine-in', 'takeaway', 'bar']),
                default => 'Service line '.($n + 1),
            };

            $line = ServiceLine::create([
                'account_id' => $account->id,
                'label' => $label,
                'did' => $did,
                'product' => $product,
                'status' => $isReseller ? 'active' : $statuses[array_rand($statuses)],
            ]);

            $this->lineIdsByAccount[$account->id][] = $line->id;
        }
    }

    private function seedBulkTickets(int $ticketTarget): void
    {
        $accountIds = array_keys($this->lineIdsByAccount);
        if ($accountIds === []) {
            return;
        }

        $types = ['fault', 'fault', 'fault', 'provisioning', 'number_port'];
        $priorities = ['low', 'normal', 'normal', 'normal', 'high', 'urgent'];
        $statuses = ['open', 'open', 'in_progress', 'in_progress', 'waiting', 'resolved', 'resolved'];

        $faultSubjects = [
            'One-way audio on outbound mobile calls',
            'No dial tone on Yealink T54W',
            'Call drops after 30 seconds',
            'Fax to email not receiving',
            'SIP registration flapping on primary trunk',
            'Echo on conference bridge',
            'Caller ID not presenting on outbound',
            'MOH silent on hunt group',
            'Intermittent 503 from carrier SBC',
            'DTMF not recognised on IVR',
            'High jitter on WAN link — voice degraded',
            'Extension unable to transfer to mobile',
        ];

        $provSubjects = [
            'Add Yealink handsets to reception ring group',
            'New user extension batch — onboarding sheet attached',
            'Update time-based routing for public holiday',
            'Provision softphone credentials for WFH staff',
            'Add DID to auto attendant menu',
            'Enable call recording on sales queue',
            'Configure mobile twinning for director line',
            'POS terminal VLAN — voice QoS check',
        ];

        $carriers = config('demo.fictional_carriers', ['Dropout Mobile', 'Maybeus']);
        $portSubjects = [
            'LNP: +61298765432 from '.$carriers[0],
            'Cat C port — 10 DIDs from '.$carriers[1],
            'Port date requested — LOA in carrier portal',
            'Reject code 6C — update account number',
            'Emergency port — fax line only',
        ];

        $rows = [];
        $references = [];

        for ($i = 0; $i < $ticketTarget; $i++) {
            $accountId = $accountIds[array_rand($accountIds)];
            $lines = $this->lineIdsByAccount[$accountId];
            $type = $types[array_rand($types)];
            $subjectPool = match ($type) {
                'fault' => $faultSubjects,
                'provisioning' => $provSubjects,
                'number_port' => $portSubjects,
            };
            $subject = $subjectPool[array_rand($subjectPool)];
            if ($i % 17 === 0) {
                $subject .= ' (ref '.strtoupper(substr(md5((string) $i), 0, 6)).')';
            }

            do {
                $reference = 'UC-'.strtoupper(bin2hex(random_bytes(3)));
            } while (isset($references[$reference]));
            $references[$reference] = true;

            $created = Carbon::now()->subDays(random_int(0, 540))->subMinutes(random_int(0, 1440));
            $updated = (clone $created)->addHours(random_int(1, 720));

            $rows[] = [
                'reference' => $reference,
                'account_id' => $accountId,
                'service_line_id' => random_int(0, 4) === 0 ? null : $lines[array_rand($lines)],
                'type' => $type,
                'subject' => $subject,
                'description' => DemoSeedRandom::maybePick(0.7, self::TICKET_DESCRIPTION_SNIPPETS),
                'priority' => $priorities[array_rand($priorities)],
                'status' => $statuses[array_rand($statuses)],
                'created_at' => $created,
                'updated_at' => $updated,
            ];

            if (count($rows) >= 400) {
                DB::table('tickets')->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('tickets')->insert($rows);
        }
    }

    private function seedShowcaseTickets(): void
    {
        $showcaseName = config('demo.showcase_account_name');
        $pacificVoice = Account::query()->where('tier', 'reseller')->orderBy('id')->first();
        $showcaseAccount = Account::query()->where('name', $showcaseName)->first()
            ?? Account::query()->where('tier', 'customer')->orderBy('id')->first();

        if (! $pacificVoice || ! $showcaseAccount) {
            return;
        }

        $pacificLine = ServiceLine::query()->where('account_id', $pacificVoice->id)->first();
        $showcaseLines = ServiceLine::query()->where('account_id', $showcaseAccount->id)->get();
        $didLine = $showcaseLines->firstWhere('product', 'did') ?? $showcaseLines->first();
        $pbxLine = $showcaseLines->firstWhere('product', 'hosted_pbx') ?? $showcaseLines->skip(1)->first();

        $now = now();

        $tickets = [
            [
                'reference' => 'UC-AU7F2K',
                'account_id' => $showcaseAccount->id,
                'service_line_id' => $pbxLine?->id,
                'type' => 'fault',
                'subject' => 'One-way audio on outbound mobile calls',
                'description' => 'Reported after firewall change. SIP 200 OK but RTP only inbound.',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'created_at' => $now->copy()->subHours(6),
                'updated_at' => $now,
            ],
            [
                'reference' => 'UC-PORT91',
                'account_id' => $pacificVoice->id,
                'service_line_id' => $pacificLine?->id,
                'type' => 'number_port',
                'subject' => 'LNP: +61298765432 from '.config('demo.fictional_carriers.0', 'Dropout Mobile'),
                'description' => 'Port date requested 2026-10-02. LOA attached in carrier portal.',
                'priority' => 'normal',
                'status' => 'waiting',
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subHours(1),
            ],
            [
                'reference' => 'UC-PROV44',
                'account_id' => $showcaseAccount->id,
                'service_line_id' => $didLine?->id,
                'type' => 'provisioning',
                'subject' => 'Add 5x Yealink T54W to reception ring group',
                'description' => 'MACs in onboarding sheet. Ship to Level 12, IssueTrackeroo Tower, '.config('demo.showcase_suburb', 'Parramatta').' NSW.',
                'priority' => 'normal',
                'status' => 'open',
                'created_at' => $now->copy()->subHours(12),
                'updated_at' => $now->copy()->subHours(12),
            ],
            [
                'reference' => 'UC-FLT88',
                'account_id' => $pacificVoice->id,
                'service_line_id' => null,
                'type' => 'fault',
                'subject' => 'Reseller API 503 on /v1/lines',
                'description' => 'Spike since 06:10 AEST. Upstream carrier ticket raised.',
                'priority' => 'high',
                'status' => 'in_progress',
                'created_at' => $now->copy()->subHours(3),
                'updated_at' => $now->copy()->subMinutes(20),
            ],
        ];

        foreach ($tickets as $row) {
            Ticket::updateOrCreate(
                ['reference' => $row['reference']],
                $row
            );
        }
    }

    private function seedStatusHistoryBackfill(): void
    {
        Ticket::query()->orderBy('id')->chunkById(300, function ($tickets) {
            $rows = [];

            foreach ($tickets as $ticket) {
                foreach ($this->buildStatusHistoryRows($ticket) as $entry) {
                    $rows[] = $entry;
                }

                if (count($rows) >= 800) {
                    DB::table('ticket_status_histories')->insert($rows);
                    $rows = [];
                }
            }

            if ($rows !== []) {
                DB::table('ticket_status_histories')->insert($rows);
            }
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildStatusHistoryRows(Ticket $ticket): array
    {
        $created = Carbon::parse($ticket->created_at ?? now());
        $end = Carbon::parse($ticket->updated_at ?? $ticket->created_at ?? now());
        if ($end->lte($created)) {
            $end = $created->copy()->addHours(6);
        }

        $transitions = $this->transitionsForTicket($ticket);
        $count = count($transitions);
        $rows = [];

        foreach ($transitions as $index => $step) {
            $fraction = ($index + 1) / $count;
            $at = $created->copy()->addSeconds(
                (int) max(60, ($end->getTimestamp() - $created->getTimestamp()) * $fraction)
            );

            $rows[] = [
                'ticket_id' => $ticket->id,
                'from_status' => $step['from'],
                'to_status' => $step['to'],
                'note' => $step['note'],
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{from: ?string, to: string, note: ?string}>
     */
    private function transitionsForTicket(Ticket $ticket): array
    {
        $target = $ticket->status;
        $variant = abs(crc32($ticket->reference.$ticket->id)) % 100;

        $paths = match ($target) {
            'open' => [
                [
                    ['from' => null, 'to' => 'open', 'note' => $this->openingNote($ticket, $variant)],
                ],
            ],
            'in_progress' => [
                [
                    ['from' => null, 'to' => 'open', 'note' => $this->openingNote($ticket, $variant)],
                    ['from' => 'open', 'to' => 'in_progress', 'note' => 'Assigned to L2 voice — initial triage.'],
                ],
                [
                    ['from' => null, 'to' => 'open', 'note' => $this->openingNote($ticket, $variant)],
                    ['from' => 'open', 'to' => 'in_progress', 'note' => 'Escalated from L1 — SIP/RTP checks underway.'],
                ],
            ],
            'waiting' => [
                [
                    ['from' => null, 'to' => 'open', 'note' => $this->openingNote($ticket, $variant)],
                    ['from' => 'open', 'to' => 'in_progress', 'note' => 'Reproduced fault on test extension.'],
                    ['from' => 'in_progress', 'to' => 'waiting', 'note' => $this->waitingNote($ticket, $variant)],
                ],
                [
                    ['from' => null, 'to' => 'open', 'note' => $this->openingNote($ticket, $variant)],
                    ['from' => 'open', 'to' => 'waiting', 'note' => $this->waitingNote($ticket, $variant)],
                ],
                [
                    ['from' => null, 'to' => 'open', 'note' => $this->openingNote($ticket, $variant)],
                    ['from' => 'open', 'to' => 'in_progress', 'note' => 'Carrier ticket opened.'],
                    ['from' => 'in_progress', 'to' => 'waiting', 'note' => 'Pending carrier maintenance window (AEST).'],
                ],
            ],
            'resolved' => [
                [
                    ['from' => null, 'to' => 'open', 'note' => $this->openingNote($ticket, $variant)],
                    ['from' => 'open', 'to' => 'in_progress', 'note' => 'Engineer engaged — logs attached.'],
                    ['from' => 'in_progress', 'to' => 'waiting', 'note' => 'Customer to validate after change window.'],
                    ['from' => 'waiting', 'to' => 'resolved', 'note' => $this->resolvedNote($ticket, $variant)],
                ],
                [
                    ['from' => null, 'to' => 'open', 'note' => $this->openingNote($ticket, $variant)],
                    ['from' => 'open', 'to' => 'in_progress', 'note' => 'Configuration updated on SBC.'],
                    ['from' => 'in_progress', 'to' => 'resolved', 'note' => $this->resolvedNote($ticket, $variant)],
                ],
                [
                    ['from' => null, 'to' => 'open', 'note' => $this->openingNote($ticket, $variant)],
                    ['from' => 'open', 'to' => 'resolved', 'note' => $this->resolvedNote($ticket, $variant)],
                ],
                [
                    ['from' => null, 'to' => 'open', 'note' => $this->openingNote($ticket, $variant)],
                    ['from' => 'open', 'to' => 'waiting', 'note' => $this->waitingNote($ticket, $variant)],
                    ['from' => 'waiting', 'to' => 'resolved', 'note' => $this->resolvedNote($ticket, $variant)],
                ],
            ],
            default => [
                [
                    ['from' => null, 'to' => 'open', 'note' => $this->openingNote($ticket, $variant)],
                ],
            ],
        };

        $set = $paths[$variant % count($paths)];

        return $this->applyShowcaseHistoryOverrides($ticket, $set);
    }

    /**
     * @param  list<array{from: ?string, to: string, note: ?string}>  $transitions
     * @return list<array{from: ?string, to: string, note: ?string}>
     */
    private function applyShowcaseHistoryOverrides(Ticket $ticket, array $transitions): array
    {
        return match ($ticket->reference) {
            'UC-AU7F2K' => [
                ['from' => null, 'to' => 'open', 'note' => 'Partner portal — one-way audio after firewall change.'],
                ['from' => 'open', 'to' => 'in_progress', 'note' => 'Capturing SIP/RTP on edge SBC; firewall rule review.'],
            ],
            'UC-PORT91' => [
                ['from' => null, 'to' => 'open', 'note' => 'LNP request submitted with LOA.'],
                ['from' => 'open', 'to' => 'in_progress', 'note' => 'Cat C validation with losing carrier.'],
                ['from' => 'in_progress', 'to' => 'waiting', 'note' => 'Port date 2026-10-02 — awaiting PN confirmation.'],
            ],
            'UC-PROV44' => [
                ['from' => null, 'to' => 'open', 'note' => 'Provisioning — 5× Yealink T54W MACs received.'],
            ],
            'UC-FLT88' => [
                ['from' => null, 'to' => 'open', 'note' => 'Automated alert — reseller API 503 spike.'],
                ['from' => 'open', 'to' => 'in_progress', 'note' => 'Upstream carrier incident linked; status page updated.'],
            ],
            default => $transitions,
        };
    }

    private function openingNote(Ticket $ticket, int $variant): string
    {
        $channel = ['Partner portal', 'Customer email', 'Reseller NOC', 'Internal monitoring'][$variant % 4];

        return match ($ticket->type) {
            'fault' => "{$channel} — fault reported.",
            'provisioning' => "{$channel} — provisioning request logged.",
            'number_port' => "{$channel} — number port case opened.",
            default => "{$channel} — ticket opened.",
        };
    }

    private function waitingNote(Ticket $ticket, int $variant): string
    {
        $notes = match ($ticket->type) {
            'fault' => [
                'Awaiting customer callback to test post-change.',
                'On hold — customer maintenance window tonight AEST.',
                'Pending third-party firewall team access.',
            ],
            'provisioning' => [
                'Hardware shipped — awaiting MAC confirmation from site.',
                'Waiting on DIDs to propagate in carrier portal.',
            ],
            'number_port' => [
                'Awaiting losing carrier PN/FOCN.',
                'Port window scheduled — customer notified.',
                'LOA mismatch — reseller correcting account number.',
            ],
            default => ['Waiting on external party.'],
        };

        return $notes[$variant % count($notes)];
    }

    private function resolvedNote(Ticket $ticket, int $variant): string
    {
        $notes = match ($ticket->type) {
            'fault' => [
                'Customer confirmed two-way audio on test calls.',
                'Root cause: one-way RTP pinhole — resolved after NAT fix.',
                'Handset re-register completed; monitoring 24h.',
            ],
            'provisioning' => [
                'Extensions live in hunt group; welcome email sent.',
                'Auto-provisioning completed for all MACs.',
            ],
            'number_port' => [
                'Port completed; inbound test successful.',
                'Number active on trunk — reseller closed loop.',
            ],
            default => ['Closed — no further action required.'],
        };

        return $notes[$variant % count($notes)];
    }
}

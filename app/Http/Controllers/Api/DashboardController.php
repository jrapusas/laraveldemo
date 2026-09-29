<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ProvisioningOrder;
use App\Models\Quote;
use App\Models\ServiceLine;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $byStatus = Ticket::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byType = Ticket::query()
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $openStatuses = array_values(array_diff(Ticket::STATUSES, ['resolved']));

        $openUrgent = Ticket::query()
            ->whereIn('status', $openStatuses)
            ->where('priority', 'urgent')
            ->count();

        return response()->json([
            'open_urgent' => $openUrgent,
            'by_status' => $byStatus,
            'by_type' => $byType,
            'quotes_by_status' => Quote::query()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status'),
            'provisioning_by_status' => ProvisioningOrder::query()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status'),
            'totals' => [
                'accounts' => Account::count(),
                'service_lines' => ServiceLine::count(),
                'tickets' => Ticket::count(),
                'open_tickets' => Ticket::query()->whereIn('status', $openStatuses)->count(),
                'quotes' => Quote::count(),
                'provisioning_orders' => ProvisioningOrder::count(),
            ],
        ]);
    }
}

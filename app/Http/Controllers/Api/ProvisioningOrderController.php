<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AppliesListSort;
use App\Http\Controllers\Controller;
use App\Models\ProvisioningOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProvisioningOrderController extends Controller
{
    use AppliesListSort;

    /** @var array<string, string> */
    private const SORT_MAP = [
        'reference' => 'provisioning_orders.reference',
        'account' => 'accounts.name',
        'title' => 'provisioning_orders.title',
        'quote' => 'quotes.reference',
        'status' => 'provisioning_orders.status',
        'requested_for' => 'provisioning_orders.requested_for',
        'created_at' => 'provisioning_orders.created_at',
    ];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(ProvisioningOrder::STATUSES)],
            'q' => ['sometimes', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:10', 'max:100'],
            ...$this->listSortValidationRules(self::SORT_MAP),
        ]);

        $query = ProvisioningOrder::query()
            ->with(['account:id,name,tier', 'quote:id,reference,title']);

        $sort = $filters['sort'] ?? null;
        if ($sort === 'account') {
            $query->leftJoin('accounts', 'provisioning_orders.account_id', '=', 'accounts.id')
                ->select('provisioning_orders.*');
        } elseif ($sort === 'quote') {
            $query->leftJoin('quotes', 'provisioning_orders.quote_id', '=', 'quotes.id')
                ->select('provisioning_orders.*');
        }
        $this->applyListSort($query, $filters, self::SORT_MAP);

        if (! empty($filters['status'])) {
            $query->where('provisioning_orders.status', $filters['status']);
        }

        if (! empty($filters['q'])) {
            $like = $this->likeContains($filters['q']);
            $query->where(function ($inner) use ($like) {
                $inner->where('provisioning_orders.reference', 'like', $like)
                    ->orWhere('provisioning_orders.title', 'like', $like);
            });
        }

        $perPage = $filters['per_page'] ?? 100;

        return response()->json(['data' => $query->paginate($perPage)]);
    }

    public function show(ProvisioningOrder $provisioningOrder): JsonResponse
    {
        $provisioningOrder->load(['account', 'quote.items']);

        return response()->json(['data' => $provisioningOrder]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AppliesListSort;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuoteController extends Controller
{
    use AppliesListSort;

    /** @var array<string, string> */
    private const SORT_MAP = [
        'reference' => 'quotes.reference',
        'account' => 'accounts.name',
        'title' => 'quotes.title',
        'subtotal_aud' => 'quotes.subtotal_aud',
        'status' => 'quotes.status',
        'valid_until' => 'quotes.valid_until',
        'created_at' => 'quotes.created_at',
    ];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(Quote::STATUSES)],
            'q' => ['sometimes', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:10', 'max:100'],
            ...$this->listSortValidationRules(self::SORT_MAP),
        ]);

        $query = Quote::query()->with(['account:id,name,tier']);

        if (($filters['sort'] ?? null) === 'account') {
            $query->leftJoin('accounts', 'quotes.account_id', '=', 'accounts.id')
                ->select('quotes.*');
        }

        $query->withCount('items');

        if (! empty($filters['status'])) {
            $query->where('quotes.status', $filters['status']);
        }

        if (! empty($filters['q'])) {
            $like = $this->likeContains($filters['q']);
            $query->where(function ($inner) use ($like) {
                $inner->where('quotes.reference', 'like', $like)
                    ->orWhere('quotes.title', 'like', $like);
            });
        }

        $this->applyListSort($query, $filters, self::SORT_MAP);

        $perPage = $filters['per_page'] ?? 100;

        return response()->json(['data' => $query->paginate($perPage)]);
    }

    public function show(Quote $quote): JsonResponse
    {
        $quote->load(['account', 'items', 'provisioningOrders' => fn ($q) => $q->latest()->limit(5)]);

        return response()->json(['data' => $quote]);
    }
}

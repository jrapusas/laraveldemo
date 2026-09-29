<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function index(): JsonResponse
    {
        $accounts = Account::query()
            ->with('serviceLines')
            ->withCount(['tickets', 'serviceLines'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $accounts]);
    }

    public function show(Account $account): JsonResponse
    {
        $account->load(['serviceLines', 'tickets' => fn ($q) => $q->latest()->limit(10)]);

        return response()->json(['data' => $account]);
    }
}

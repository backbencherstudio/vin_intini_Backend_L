<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\DeletedAccountLog;
use Carbon\Carbon;
use Illuminate\Http\Request;


class UserManagementController extends Controller
{
    public function getDeletedAccountLogs(Request $request)
    {
        $search = $request->input('search');

        $deletedAccounts = DeletedAccountLog::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('user_name', 'like', "%{$search}%")
                        ->orWhere('user_email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->input('per_page', 20));

        $data = $deletedAccounts->getCollection()->map(function ($account, $index) use ($deletedAccounts) {
            return [
                'sl_no' => (($deletedAccounts->currentPage() - 1) * $deletedAccounts->perPage()) + $index + 1,
                'id' => $account->id,
                'user_id' => $account->user_id,
                'full_name' => $account->user_name,
                'email' => $account->user_email,
                'reason' => $account->reason,
                'request_date' => Carbon::parse($account->requested_at)->format('d M Y'),
                'permanently_delete' => Carbon::parse($account->permanent_delete_at)->format('d M Y'),
                'remaining_days' => $account->permanent_delete_at
                    ? max(0, Carbon::now()->startOfDay()->diffInDays(
                        Carbon::parse($account->permanent_delete_at)->startOfDay(),
                        false
                    )) . ' Days'
                    : null,
            ];
        });

        return response()->json([
            'success' => true,
            'total_request' => DeletedAccountLog::count(),
            'data' => $data,

            'pagination' => [
                'current_page' => $deletedAccounts->currentPage(),
                'per_page' => $deletedAccounts->perPage(),
                'total' => $deletedAccounts->total(),
                'last_page' => $deletedAccounts->lastPage(),
                'from' => $deletedAccounts->firstItem(),
                'to' => $deletedAccounts->lastItem(),
            ],
        ]);
    }
}

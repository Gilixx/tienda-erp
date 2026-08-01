<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /** GET /api/admin/audit-logs */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,name,email')->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }
        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->date('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->date('hasta'));
        }

        return response()->json(
            $query->paginate(min(100, $request->integer('per_page', 30)))
        );
    }
}

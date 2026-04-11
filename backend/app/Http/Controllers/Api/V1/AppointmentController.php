<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /**
     * List the authenticated customer's appointments.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->isCustomer()) {
            abort(403, 'Only customers can list their appointments.');
        }

        $appointments = Appointment::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'data' => AppointmentResource::collection($appointments),
            'meta' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
            ],
        ]);
    }
}

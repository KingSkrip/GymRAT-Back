<?php

namespace App\Http\Controllers\Membresias;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MembershipController extends Controller
{
    public function store(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'type'       => 'required|in:visit,monthly,yearly,custom',
            'price'      => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        // Desactivar membresía anterior
        Membership::where('user_id', $userId)->update(['is_active' => false]);

        // Calcular end_date automático si no viene
        if (!isset($validated['end_date'])) {
            $start = Carbon::parse($validated['start_date']);
            $validated['end_date'] = match($validated['type']) {
                'monthly' => $start->addMonth()->toDateString(),
                'yearly'  => $start->addYear()->toDateString(),
                'visit'   => $validated['start_date'],
                default   => null,
            };
        }

        $membership = Membership::create([
            'user_id'    => $userId,
            'type'       => $validated['type'],
            'price'      => $validated['price'],
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
            'is_active'  => true,
        ]);

        return response()->json(['success' => true, 'membership' => $membership], 201);
    }
}
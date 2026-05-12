<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LotVerification;
use Illuminate\Http\Request;

class LotVerificationController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = LotVerification::with(['user:id,name,email', 'agent:id,name,email']);
        if ($user->hasRole('client'))      $query->where('user_id', $user->id);
        elseif ($user->hasRole('agent'))   $query->where('assigned_agent_id', $user->id);
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lot_number'    => 'required|string',
            'block_number'  => 'nullable|string',
            'survey_number' => 'nullable|string',
            'municipality'  => 'required|string',
            'province'      => 'required|string',
            'area_sqm'      => 'nullable|numeric',
        ]);
        $record = LotVerification::create(array_merge($data, ['user_id' => $request->user()->id]));
        return response()->json($record, 201);
    }

    public function show(Request $request, LotVerification $lotVerification)
    {
        $this->authorizeAccess($request->user(), $lotVerification);
        return response()->json($lotVerification->load(['user:id,name,email', 'agent:id,name,email']));
    }

    public function update(Request $request, LotVerification $lotVerification)
    {
        $this->authorizeAccess($request->user(), $lotVerification);
        $data = $request->validate([
            'status'            => 'sometimes|in:pending,processing,completed,rejected',
            'remarks'           => 'nullable|string',
            'assigned_agent_id' => 'nullable|exists:users,id',
            'lot_number'        => 'sometimes|string',
            'block_number'      => 'nullable|string',
            'survey_number'     => 'nullable|string',
            'municipality'      => 'sometimes|string',
            'province'          => 'sometimes|string',
            'area_sqm'          => 'nullable|numeric',
        ]);
        $lotVerification->update($data);
        return response()->json($lotVerification->fresh(['user:id,name,email', 'agent:id,name,email']));
    }

    public function destroy(Request $request, LotVerification $lotVerification)
    {
        $this->authorizeAccess($request->user(), $lotVerification);
        $lotVerification->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }

    private function authorizeAccess($user, $record)
    {
        if ($user->hasRole('client') && $record->user_id !== $user->id) abort(403);
        if ($user->hasRole('agent') && $record->assigned_agent_id !== $user->id) abort(403);
    }
}

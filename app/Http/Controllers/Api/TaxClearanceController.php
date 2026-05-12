<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxClearance;
use Illuminate\Http\Request;

class TaxClearanceController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = TaxClearance::with(['user:id,name,email', 'agent:id,name,email']);
        if ($user->hasRole('client'))      $query->where('user_id', $user->id);
        elseif ($user->hasRole('agent'))   $query->where('assigned_agent_id', $user->id);
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tax_declaration_number' => 'required|string',
            'property_owner'         => 'required|string',
            'property_address'       => 'required|string',
            'year_covered'           => 'required|digits:4|integer',
        ]);
        $record = TaxClearance::create(array_merge($data, ['user_id' => $request->user()->id]));
        return response()->json($record, 201);
    }

    public function show(Request $request, TaxClearance $taxClearance)
    {
        $this->authorizeAccess($request->user(), $taxClearance);
        return response()->json($taxClearance->load(['user:id,name,email', 'agent:id,name,email']));
    }

    public function update(Request $request, TaxClearance $taxClearance)
    {
        $this->authorizeAccess($request->user(), $taxClearance);
        $data = $request->validate([
            'status'                 => 'sometimes|in:pending,processing,completed,rejected',
            'remarks'                => 'nullable|string',
            'assigned_agent_id'      => 'nullable|exists:users,id',
            'tax_declaration_number' => 'sometimes|string',
            'property_owner'         => 'sometimes|string',
            'property_address'       => 'sometimes|string',
            'year_covered'           => 'sometimes|digits:4|integer',
        ]);
        $taxClearance->update($data);
        return response()->json($taxClearance->fresh(['user:id,name,email', 'agent:id,name,email']));
    }

    public function destroy(Request $request, TaxClearance $taxClearance)
    {
        $this->authorizeAccess($request->user(), $taxClearance);
        $taxClearance->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }

    private function authorizeAccess($user, $record)
    {
        if ($user->hasRole('client') && $record->user_id !== $user->id) abort(403);
        if ($user->hasRole('agent') && $record->assigned_agent_id !== $user->id) abort(403);
    }
}

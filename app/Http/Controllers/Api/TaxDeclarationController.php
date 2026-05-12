<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxDeclaration;
use Illuminate\Http\Request;

class TaxDeclarationController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = TaxDeclaration::with(['user:id,name,email', 'agent:id,name,email']);
        if ($user->hasRole('client'))      $query->where('user_id', $user->id);
        elseif ($user->hasRole('agent'))   $query->where('assigned_agent_id', $user->id);
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tax_declaration_number' => 'nullable|string',
            'property_owner'         => 'required|string',
            'property_address'       => 'required|string',
            'property_type'          => 'required|string',
            'municipality'           => 'required|string',
            'province'               => 'required|string',
            'request_type'           => 'required|in:new,transfer,correction,cancellation',
        ]);
        $record = TaxDeclaration::create(array_merge($data, ['user_id' => $request->user()->id]));
        return response()->json($record, 201);
    }

    public function show(Request $request, TaxDeclaration $taxDeclaration)
    {
        $this->authorizeAccess($request->user(), $taxDeclaration);
        return response()->json($taxDeclaration->load(['user:id,name,email', 'agent:id,name,email']));
    }

    public function update(Request $request, TaxDeclaration $taxDeclaration)
    {
        $this->authorizeAccess($request->user(), $taxDeclaration);
        $data = $request->validate([
            'status'                 => 'sometimes|in:pending,processing,completed,rejected',
            'remarks'                => 'nullable|string',
            'assigned_agent_id'      => 'nullable|exists:users,id',
            'tax_declaration_number' => 'nullable|string',
            'property_owner'         => 'sometimes|string',
            'property_address'       => 'sometimes|string',
            'property_type'          => 'sometimes|string',
            'municipality'           => 'sometimes|string',
            'province'               => 'sometimes|string',
            'request_type'           => 'sometimes|in:new,transfer,correction,cancellation',
        ]);
        $taxDeclaration->update($data);
        return response()->json($taxDeclaration->fresh(['user:id,name,email', 'agent:id,name,email']));
    }

    public function destroy(Request $request, TaxDeclaration $taxDeclaration)
    {
        $this->authorizeAccess($request->user(), $taxDeclaration);
        $taxDeclaration->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }

    private function authorizeAccess($user, $record)
    {
        if ($user->hasRole('client') && $record->user_id !== $user->id) abort(403);
        if ($user->hasRole('agent') && $record->assigned_agent_id !== $user->id) abort(403);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mortgage;
use Illuminate\Http\Request;

class MortgageController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Mortgage::with(['user:id,name,email', 'agent:id,name,email']);
        if ($user->hasRole('client'))      $query->where('user_id', $user->id);
        elseif ($user->hasRole('agent'))   $query->where('assigned_agent_id', $user->id);
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title_number'     => 'required|string',
            'mortgagor_name'   => 'required|string',
            'mortgagee_name'   => 'required|string',
            'loan_amount'      => 'required|numeric|min:0',
            'mortgage_date'    => 'nullable|date',
            'transaction_type' => 'required|in:registration,cancellation',
        ]);
        $record = Mortgage::create(array_merge($data, ['user_id' => $request->user()->id]));
        return response()->json($record, 201);
    }

    public function show(Request $request, Mortgage $mortgage)
    {
        $this->authorizeAccess($request->user(), $mortgage);
        return response()->json($mortgage->load(['user:id,name,email', 'agent:id,name,email']));
    }

    public function update(Request $request, Mortgage $mortgage)
    {
        $this->authorizeAccess($request->user(), $mortgage);
        $data = $request->validate([
            'status'            => 'sometimes|in:pending,processing,completed,rejected',
            'remarks'           => 'nullable|string',
            'assigned_agent_id' => 'nullable|exists:users,id',
            'title_number'      => 'sometimes|string',
            'mortgagor_name'    => 'sometimes|string',
            'mortgagee_name'    => 'sometimes|string',
            'loan_amount'       => 'sometimes|numeric|min:0',
            'mortgage_date'     => 'nullable|date',
            'transaction_type'  => 'sometimes|in:registration,cancellation',
        ]);
        $mortgage->update($data);
        return response()->json($mortgage->fresh(['user:id,name,email', 'agent:id,name,email']));
    }

    public function destroy(Request $request, Mortgage $mortgage)
    {
        $this->authorizeAccess($request->user(), $mortgage);
        $mortgage->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }

    private function authorizeAccess($user, $record)
    {
        if ($user->hasRole('client') && $record->user_id !== $user->id) abort(403);
        if ($user->hasRole('agent') && $record->assigned_agent_id !== $user->id) abort(403);
    }
}

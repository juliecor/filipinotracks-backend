<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TitleTransfer;
use Illuminate\Http\Request;

class TitleTransferController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = TitleTransfer::with(['user:id,name,email', 'agent:id,name,email']);
        if ($user->hasRole('client'))      $query->where('user_id', $user->id);
        elseif ($user->hasRole('agent'))   $query->where('assigned_agent_id', $user->id);
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title_number'     => 'required|string',
            'seller_name'      => 'required|string',
            'buyer_name'       => 'required|string',
            'property_address' => 'required|string',
            'sale_amount'      => 'nullable|numeric',
            'transfer_date'    => 'nullable|date',
        ]);
        $record = TitleTransfer::create(array_merge($data, ['user_id' => $request->user()->id]));
        return response()->json($record, 201);
    }

    public function show(Request $request, TitleTransfer $titleTransfer)
    {
        $this->authorizeAccess($request->user(), $titleTransfer);
        return response()->json($titleTransfer->load(['user:id,name,email', 'agent:id,name,email']));
    }

    public function update(Request $request, TitleTransfer $titleTransfer)
    {
        $this->authorizeAccess($request->user(), $titleTransfer);
        $data = $request->validate([
            'status'            => 'sometimes|in:pending,processing,completed,rejected',
            'remarks'           => 'nullable|string',
            'assigned_agent_id' => 'nullable|exists:users,id',
            'seller_name'       => 'sometimes|string',
            'buyer_name'        => 'sometimes|string',
            'property_address'  => 'sometimes|string',
            'sale_amount'       => 'nullable|numeric',
            'transfer_date'     => 'nullable|date',
        ]);
        $titleTransfer->update($data);
        return response()->json($titleTransfer->fresh(['user:id,name,email', 'agent:id,name,email']));
    }

    public function destroy(Request $request, TitleTransfer $titleTransfer)
    {
        $this->authorizeAccess($request->user(), $titleTransfer);
        $titleTransfer->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }

    private function authorizeAccess($user, $record)
    {
        if ($user->hasRole('client') && $record->user_id !== $user->id) abort(403);
        if ($user->hasRole('agent') && $record->assigned_agent_id !== $user->id) abort(403);
    }
}

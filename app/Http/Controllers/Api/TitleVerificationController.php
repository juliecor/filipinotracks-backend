<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TitleVerification;
use Illuminate\Http\Request;

class TitleVerificationController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = TitleVerification::with(['user:id,name,email', 'agent:id,name,email']);

        if ($user->hasRole('client')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('agent')) {
            $query->where('assigned_agent_id', $user->id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title_number'     => 'required|string',
            'property_owner'   => 'required|string',
            'property_address' => 'required|string',
            'lot_number'       => 'nullable|string',
            'block_number'     => 'nullable|string',
            'survey_number'    => 'nullable|string',
        ]);

        $record = TitleVerification::create(array_merge($data, ['user_id' => $request->user()->id]));

        return response()->json($record, 201);
    }

    public function show(Request $request, TitleVerification $titleVerification)
    {
        $this->authorizeAccess($request->user(), $titleVerification);
        return response()->json($titleVerification->load(['user:id,name,email', 'agent:id,name,email']));
    }

    public function update(Request $request, TitleVerification $titleVerification)
    {
        $this->authorizeAccess($request->user(), $titleVerification);

        $data = $request->validate([
            'status'            => 'sometimes|in:pending,processing,completed,rejected',
            'remarks'           => 'nullable|string',
            'assigned_agent_id' => 'nullable|exists:users,id',
            'title_number'      => 'sometimes|string',
            'property_owner'    => 'sometimes|string',
            'property_address'  => 'sometimes|string',
            'lot_number'        => 'nullable|string',
            'block_number'      => 'nullable|string',
            'survey_number'     => 'nullable|string',
        ]);

        $titleVerification->update($data);

        return response()->json($titleVerification->fresh(['user:id,name,email', 'agent:id,name,email']));
    }

    public function destroy(Request $request, TitleVerification $titleVerification)
    {
        $this->authorizeAccess($request->user(), $titleVerification);
        $titleVerification->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }

    private function authorizeAccess($user, $record)
    {
        if ($user->hasRole('client') && $record->user_id !== $user->id) {
            abort(403);
        }
        if ($user->hasRole('agent') && $record->assigned_agent_id !== $user->id) {
            abort(403);
        }
    }
}

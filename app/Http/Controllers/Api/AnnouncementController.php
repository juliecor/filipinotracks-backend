<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::with('createdBy:id,name')->latest();

        if (!$request->user()->hasRole('admin')) {
            $query->where('is_published', true);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string',
            'type'         => 'required|in:info,warning,success,urgent',
            'is_published' => 'boolean',
        ]);

        $announcement = Announcement::create(array_merge($data, [
            'created_by'   => $request->user()->id,
            'published_at' => ($data['is_published'] ?? false) ? now() : null,
        ]));

        return response()->json($announcement->load('createdBy:id,name'), 201);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'body'         => 'sometimes|string',
            'type'         => 'sometimes|in:info,warning,success,urgent',
            'is_published' => 'boolean',
        ]);

        if (isset($data['is_published']) && $data['is_published'] && !$announcement->is_published) {
            $data['published_at'] = now();
        }

        $announcement->update($data);
        return response()->json($announcement->load('createdBy:id,name'));
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return response()->json(['message' => 'Announcement deleted.']);
    }
}

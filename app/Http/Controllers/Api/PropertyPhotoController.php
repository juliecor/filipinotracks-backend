<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyMap;
use App\Models\PropertyPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyPhotoController extends Controller
{
    /**
     * GET /api/property-maps/{propertyMap}/photos
     */
    public function index(PropertyMap $propertyMap)
    {
        return response()->json($propertyMap->photos()->get());
    }

    /**
     * POST /api/property-maps/{propertyMap}/photos
     *
     * Admin/staff only. Accepts one or more image files in `photos[]`.
     */
    public function store(Request $request, PropertyMap $propertyMap)
    {
        $this->authorizeStaff($request);

        $request->validate([
            'photos'    => 'required|array|min:1|max:8',
            'photos.*'  => 'required|image|mimes:jpeg,jpg,png,webp|max:8192', // 8 MB each
        ]);

        $code = $propertyMap->transaction?->transaction_code ?? "map-{$propertyMap->id}";
        $nextOrder = ($propertyMap->photos()->max('sort_order') ?? -1) + 1;

        $created = [];
        foreach ($request->file('photos', []) as $file) {
            $path = $file->store("property-photos/{$code}", 's3');
            $photo = $propertyMap->photos()->create([
                'uploaded_by' => $request->user()->id,
                'file_path'   => $path,
                'file_type'   => $file->getMimeType(),
                'file_size'   => $file->getSize(),
                'sort_order'  => $nextOrder++,
            ]);
            $created[] = $photo;
        }

        return response()->json($created, 201);
    }

    /**
     * PUT /api/property-photos/{photo}
     */
    public function update(Request $request, PropertyPhoto $photo)
    {
        $this->authorizeStaff($request);

        $data = $request->validate([
            'caption' => 'nullable|string|max:240',
        ]);

        $photo->update($data);
        return response()->json($photo->fresh());
    }

    /**
     * DELETE /api/property-photos/{photo}
     */
    public function destroy(Request $request, PropertyPhoto $photo)
    {
        $this->authorizeStaff($request);

        Storage::disk('s3')->delete($photo->file_path);
        $photo->delete();

        return response()->json(['message' => 'Photo deleted.']);
    }

    /**
     * PUT /api/property-maps/{propertyMap}/photos/reorder
     *
     * Body: { "order": [photoId, photoId, photoId, ...] }
     */
    public function reorder(Request $request, PropertyMap $propertyMap)
    {
        $this->authorizeStaff($request);

        $data = $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer',
        ]);

        foreach ($data['order'] as $index => $photoId) {
            $propertyMap->photos()
                ->where('id', $photoId)
                ->update(['sort_order' => $index]);
        }

        return response()->json($propertyMap->photos()->get());
    }

    private function authorizeStaff(Request $request): void
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('staff'))) {
            abort(403, 'Only admin or staff can manage property photos.');
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    // Public: approved testimonials for landing page
    public function index()
    {
        $testimonials = Testimonial::with('user:id,name,profile_picture')
            ->where('status', 'approved')
            ->latest()
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'name'       => $t->user->name,
                'avatar_url' => $t->user->profile_picture_url,
                'role_label' => $t->role_label,
                'rating'     => $t->rating,
                'content'    => $t->content,
                'created_at' => $t->created_at,
            ]);

        return response()->json($testimonials);
    }

    // Client: submit a testimonial (one per user)
    public function store(Request $request)
    {
        $request->validate([
            'role_label' => 'nullable|string|max:120',
            'rating'     => 'required|integer|min:1|max:5',
            'content'    => 'required|string|min:20|max:1000',
        ]);

        $existing = Testimonial::where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You already have a testimonial submitted or approved.'], 422);
        }

        $testimonial = Testimonial::create([
            'user_id'    => $request->user()->id,
            'role_label' => $request->role_label,
            'rating'     => $request->rating,
            'content'    => $request->content,
            'status'     => 'pending',
        ]);

        return response()->json($testimonial, 201);
    }

    // Client: get own testimonial status
    public function mine(Request $request)
    {
        $t = Testimonial::where('user_id', $request->user()->id)->latest()->first();
        return response()->json($t);
    }

    // Admin: list all testimonials
    public function adminIndex()
    {
        $testimonials = Testimonial::with('user:id,name,profile_picture')
            ->latest()
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'name'       => $t->user->name,
                'avatar_url' => $t->user->profile_picture_url,
                'role_label' => $t->role_label,
                'rating'     => $t->rating,
                'content'    => $t->content,
                'status'     => $t->status,
                'created_at' => $t->created_at,
            ]);

        return response()->json($testimonials);
    }

    // Admin: approve or reject
    public function updateStatus(Request $request, Testimonial $testimonial)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);
        $testimonial->update(['status' => $request->status]);
        return response()->json($testimonial);
    }
}

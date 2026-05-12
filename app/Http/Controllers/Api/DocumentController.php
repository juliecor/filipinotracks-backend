<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(Request $request, Transaction $transaction)
    {
        $request->validate([
            'file'          => 'required|file|max:20480',
            'document_type' => 'nullable|string|max:100',
            'description'   => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents/' . $transaction->transaction_code, 'public');

        $document = $transaction->documents()->create([
            'uploaded_by'   => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path'     => $path,
            'file_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),
            'document_type' => $request->document_type,
            'description'   => $request->description,
        ]);

        return response()->json($document->append('url'), 201);
    }

    public function destroy(Request $request, Document $document)
    {
        $user = $request->user();
        if (!$user->hasRole('admin') && $document->uploaded_by !== $user->id) {
            abort(403);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Document deleted.']);
    }
}

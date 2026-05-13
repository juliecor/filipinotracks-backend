<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NewMessageReceived;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    private function shape(Message $m): array
    {
        return [
            'id'             => $m->id,
            'sender_id'      => $m->sender_id,
            'sender_name'    => $m->sender->name,
            'sender_avatar'  => $m->sender->profile_picture_url,
            'body'           => $m->body,
            'attachment_url' => $m->attachment_path
                                    ? Storage::disk('s3')->url($m->attachment_path)
                                    : null,
            'created_at'     => $m->created_at,
        ];
    }

    // GET /transactions/{transaction}/messages
    public function index(Request $request, Transaction $transaction)
    {
        $userId = $request->user()->id;
        $role   = $request->user()->roles->first()?->name;

        if ($role === 'client' && $transaction->user_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Mark messages to this user as read
        Message::where('transaction_id', $transaction->id)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $transaction->messages()
            ->with('sender:id,name,profile_picture')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => $this->shape($m));

        return response()->json($messages);
    }

    // POST /transactions/{transaction}/messages
    public function store(Request $request, Transaction $transaction)
    {
        $request->validate([
            'body'       => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:8192',
        ]);

        if (!$request->filled('body') && !$request->hasFile('attachment')) {
            return response()->json(['message' => 'A message or image is required.'], 422);
        }

        $senderId = $request->user()->id;
        $role     = $request->user()->roles->first()?->name;

        if ($role === 'client' && $transaction->user_id !== $senderId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($role === 'client') {
            $receiverId = $transaction->assigned_staff_id
                ?? User::role('admin')->value('id');
        } else {
            $receiverId = $transaction->user_id;
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat', 's3');
        }

        $message = Message::create([
            'transaction_id'  => $transaction->id,
            'sender_id'       => $senderId,
            'receiver_id'     => $receiverId,
            'body'            => $request->body ?? '',
            'attachment_path' => $attachmentPath,
        ]);

        $message->load('sender:id,name,profile_picture');

        // Notify receiver
        if ($receiverId) {
            $notifBody = $request->filled('body')
                ? Str::limit($request->body, 80)
                : '📷 Sent a photo';

            Notification::create([
                'user_id' => $receiverId,
                'type'    => 'new_message',
                'title'   => 'New message from ' . $request->user()->name,
                'body'    => $notifBody,
                'data'    => [
                    'transaction_id'   => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'sender_id'        => $senderId,
                ],
            ]);
        }

        // Email receiver
        $receiver = User::find($receiverId);
        if ($receiver?->email) {
            $mail = Mail::to($receiver->email);
            $mail->send(new NewMessageReceived($transaction, $message, $request->user()->name));
        }

        return response()->json($this->shape($message), 201);
    }

    // GET /messages/conversations
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;
        $role   = $request->user()->roles->first()?->name;

        $txIds = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->pluck('transaction_id')
            ->unique()
            ->filter()
            ->values();

        $transactions = Transaction::whereIn('id', $txIds)
            ->with([
                'user:id,name,profile_picture',
                'assignedStaff:id,name,profile_picture',
            ])
            ->get();

        $conversations = $transactions->map(function ($tx) use ($userId, $role) {
            $lastMsg = Message::where('transaction_id', $tx->id)
                ->orderByDesc('created_at')
                ->first();

            $unread = Message::where('transaction_id', $tx->id)
                ->where('receiver_id', $userId)
                ->whereNull('read_at')
                ->count();

            $other = $role === 'client' ? $tx->assignedStaff : $tx->user;

            return [
                'transaction_id'   => $tx->id,
                'transaction_code' => $tx->transaction_code,
                'service_type'     => $tx->service_type,
                'other_name'       => $other?->name ?? 'Support Team',
                'other_avatar'     => $other?->profile_picture_url,
                'last_message'     => $lastMsg?->body ?: ($lastMsg?->attachment_path ? '📷 Photo' : null),
                'last_message_at'  => $lastMsg?->created_at,
                'unread_count'     => $unread,
            ];
        })
        ->sortByDesc('last_message_at')
        ->values();

        return response()->json($conversations);
    }

    // GET /messages/unread-count
    public function unreadCount(Request $request)
    {
        $count = Message::where('receiver_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}

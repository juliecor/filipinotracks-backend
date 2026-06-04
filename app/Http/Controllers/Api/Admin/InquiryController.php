<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InquiryReply as InquiryReplyMail;
use App\Models\Inquiry;
use App\Models\InquiryReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InquiryController extends Controller
{
    /** Relations loaded for every inquiry returned to the admin UI. */
    private const WITH = [
        'transaction:id,transaction_code,status,service_type',
        'propertyMap:id,transaction_id,registered_owner,title_number,province,city_municipality',
        'respondedBy:id,name',
        'replies.user:id,name',
    ];

    /**
     * GET /api/admin/inquiries
     *
     * Returns every property inquiry with the property + transaction it's
     * attached to. Supports a `status` filter and a free-text `q` filter
     * across name/email/phone/message/transaction_code.
     */
    public function index(Request $request)
    {
        $query = Inquiry::query()
            ->with(self::WITH)
            ->latest('created_at');

        if ($status = $request->query('status')) {
            if (in_array($status, ['new', 'contacted', 'closed'], true)) {
                $query->where('status', $status);
            }
        }

        if ($q = trim((string) $request->query('q', ''))) {
            $like = '%' . $q . '%';
            $query->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('phone', 'like', $like)
                  ->orWhere('message', 'like', $like)
                  ->orWhereHas('transaction', fn($t) => $t->where('transaction_code', 'like', $like));
            });
        }

        // Counts by status (always over the unfiltered set so the tab badges
        // stay stable as the user toggles between tabs).
        $counts = [
            'all'       => Inquiry::count(),
            'new'       => Inquiry::where('status', 'new')->count(),
            'contacted' => Inquiry::where('status', 'contacted')->count(),
            'closed'    => Inquiry::where('status', 'closed')->count(),
        ];

        return response()->json([
            'data'   => $query->get(),
            'counts' => $counts,
        ]);
    }

    /**
     * PUT /api/admin/inquiries/{inquiry}
     *
     * Updates inquiry status. When moving to "contacted" or "closed" we
     * stamp the current admin as the responder and record the time.
     */
    public function update(Request $request, Inquiry $inquiry)
    {
        $data = $request->validate([
            'status' => 'required|in:new,contacted,closed',
        ]);

        $payload = ['status' => $data['status']];
        if ($data['status'] === 'new') {
            // Reset response stamps when reopening
            $payload['responded_by'] = null;
            $payload['responded_at'] = null;
        } else {
            $payload['responded_by'] = $request->user()->id;
            $payload['responded_at'] = now();
        }

        $inquiry->update($payload);

        return response()->json($inquiry->fresh(self::WITH));
    }

    /**
     * POST /api/admin/inquiries/{inquiry}/reply
     *
     * Sends a reply email to the buyer straight from the dashboard, records
     * the reply, and marks the inquiry as contacted.
     */
    public function reply(Request $request, Inquiry $inquiry)
    {
        $data = $request->validate([
            'message' => 'required|string|max:4000',
        ]);

        if (empty($inquiry->email)) {
            return response()->json([
                'message' => 'This inquiry has no email on file. Use the Viber button to reach them by phone.',
            ], 422);
        }

        $inquiry->load('transaction', 'propertyMap');
        $staffName = $request->user()->name ?: 'FilipinoTracks Team';

        // Send the reply to the buyer. If mail fails, don't record or mark
        // contacted — so the staff knows it didn't go through.
        try {
            $mail = Mail::to($inquiry->email);
            if ($cc = env('MAIL_CC')) $mail->cc($cc);
            $mail->send(new InquiryReplyMail($inquiry, $data['message'], $staffName));
        } catch (\Throwable $e) {
            Log::warning('Inquiry reply email failed: ' . $e->getMessage(), ['inquiry_id' => $inquiry->id]);
            return response()->json([
                'message' => 'Could not send the email right now. Please try again shortly.',
            ], 500);
        }

        InquiryReply::create([
            'inquiry_id' => $inquiry->id,
            'user_id'    => $request->user()->id,
            'message'    => $data['message'],
            'channel'    => 'email',
        ]);

        $inquiry->update([
            'status'       => 'contacted',
            'responded_by' => $request->user()->id,
            'responded_at' => now(),
        ]);

        return response()->json($inquiry->fresh(self::WITH));
    }
}

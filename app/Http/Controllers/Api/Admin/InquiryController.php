<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
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
            ->with([
                'transaction:id,transaction_code,status,service_type',
                'propertyMap:id,transaction_id,registered_owner,title_number,province,city_municipality',
                'respondedBy:id,name',
            ])
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

        return response()->json(
            $inquiry->fresh([
                'transaction:id,transaction_code,status,service_type',
                'propertyMap:id,transaction_id,registered_owner,title_number,province,city_municipality',
                'respondedBy:id,name',
            ])
        );
    }
}

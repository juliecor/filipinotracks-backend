<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminMessageToClient;
use App\Mail\LandTitleVerificationApproved;
use App\Mail\TransactionStatusChanged;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Transaction::with(['user:id,name,email', 'assignedStaff:id,name,email', 'documents']);

        if ($user->hasRole('client')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('staff') || $user->hasRole('agent')) {
            $query->where('assigned_staff_id', $user->id);
        }

        if ($request->status)       $query->where('status', $request->status);
        if ($request->service_type) $query->where('service_type', $request->service_type);
        if ($request->search) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('transaction_code', 'like', $s)
                  ->orWhere('registered_owner', 'like', $s)
                  ->orWhere('property_address', 'like', $s);
            });
        }

        return response()->json($query->latest()->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'service_type'           => 'required|in:title-verification,title-cancellation,land-registration,property-consultation',
            'property_title_number'  => 'nullable|string',
            'lot_number'             => 'nullable|string',
            'block_number'           => 'nullable|string',
            'tax_declaration_number' => 'nullable|string',
            'property_address'       => 'nullable|string',
            'property_type'          => 'nullable|string',
            'lot_area'               => 'nullable|numeric',
            'registered_owner'       => 'nullable|string',
            'transfer_type'          => 'nullable|string',
            'remarks'                => 'nullable|string',
        ]);

        $transaction = Transaction::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'status'  => 'submitted',
        ]));

        TransactionLog::create([
            'transaction_id' => $transaction->id,
            'performed_by'   => $request->user()->id,
            'action'         => 'Transaction submitted',
            'to_status'      => 'submitted',
        ]);

        return response()->json($transaction->load(['user:id,name,email']), 201);
    }

    public function show(Request $request, Transaction $transaction)
    {
        $this->authorizeAccess($request->user(), $transaction);

        return response()->json(
            $transaction->load(['user:id,name,email', 'assignedStaff:id,name,email', 'logs.performedBy:id,name', 'documents', 'payments'])
        );
    }

    public function update(Request $request, Transaction $transaction)
    {
        $user = $request->user();
        $this->authorizeAccess($user, $transaction);

        $data = $request->validate([
            'status'                 => 'sometimes|in:submitted,under review,verification ongoing,processing,waiting for requirements,pending approval,approved,released,rejected',
            'remarks'                => 'nullable|string',
            'assigned_staff_id'      => 'nullable|exists:users,id',
            'property_title_number'  => 'nullable|string',
            'lot_number'             => 'nullable|string',
            'block_number'           => 'nullable|string',
            'tax_declaration_number' => 'nullable|string',
            'property_address'       => 'nullable|string',
            'property_type'          => 'nullable|string',
            'lot_area'               => 'nullable|numeric',
            'registered_owner'       => 'nullable|string',
            'service_fee'            => 'nullable|numeric',
        ]);

        // Two-tier approval: only admin can set the final-verdict statuses.
        $adminOnly = ['approved', 'released', 'rejected'];
        if (isset($data['status']) && in_array($data['status'], $adminOnly, true) && !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Only an admin can set this status. Staff should mark as "Pending Approval" first.',
            ], 403);
        }

        $oldStatus = $transaction->status;
        $transaction->update($data);

        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            TransactionLog::create([
                'transaction_id' => $transaction->id,
                'performed_by'   => $user->id,
                'action'         => 'Status updated',
                'from_status'    => $oldStatus,
                'to_status'      => $data['status'],
                'notes'          => $data['remarks'] ?? null,
            ]);

            // Notify transaction owner
            Notification::create([
                'user_id' => $transaction->user_id,
                'type'    => 'transaction_update',
                'title'   => 'Transaction Status Updated',
                'body'    => "Your transaction {$transaction->transaction_code} status changed to: {$data['status']}.",
                'data'    => ['transaction_id' => $transaction->id, 'status' => $data['status']],
            ]);

            // Notify assigned staff if different from actor
            if ($transaction->assigned_staff_id && $transaction->assigned_staff_id !== $user->id) {
                Notification::create([
                    'user_id' => $transaction->assigned_staff_id,
                    'type'    => 'transaction_update',
                    'title'   => 'Transaction Updated',
                    'body'    => "Transaction {$transaction->transaction_code} status changed to: {$data['status']}.",
                    'data'    => ['transaction_id' => $transaction->id, 'status' => $data['status']],
                ]);
            }

            // Email client
            $transaction->load('user');
            if ($transaction->user?->email) {
                $mail = Mail::to($transaction->user->email);
                if ($cc = env('MAIL_CC')) $mail->cc($cc);

                // Special branded email when a Land / Title Verification is APPROVED —
                // includes a satellite map snapshot of the verified property.
                $isApproved = $data['status'] === 'approved';
                $isLandTitle = $transaction->service_type === 'title-verification';

                try {
                    if ($isApproved && $isLandTitle) {
                        $mail->send(new LandTitleVerificationApproved($transaction));
                    } else {
                        $mail->send(new TransactionStatusChanged(
                            $transaction,
                            $oldStatus,
                            $data['status'],
                            $data['remarks'] ?? null,
                        ));
                    }
                } catch (\Throwable $e) {
                    // Don't fail the API request if the mail server hiccups
                    Log::warning('Status-change email failed: ' . $e->getMessage(), [
                        'transaction_id' => $transaction->id,
                        'new_status'     => $data['status'],
                    ]);
                }
            }
        }

        // Notify staff when assigned
        if (isset($data['assigned_staff_id']) && $data['assigned_staff_id'] && $data['assigned_staff_id'] != $transaction->getOriginal('assigned_staff_id')) {
            Notification::create([
                'user_id' => $data['assigned_staff_id'],
                'type'    => 'assignment',
                'title'   => 'New Transaction Assigned',
                'body'    => "You have been assigned to transaction {$transaction->transaction_code}.",
                'data'    => ['transaction_id' => $transaction->id],
            ]);
        }

        return response()->json(
            $transaction->fresh(['user:id,name,email', 'assignedStaff:id,name,email', 'logs.performedBy:id,name', 'documents', 'payments'])
        );
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        if (!$request->user()->hasRole('admin')) abort(403);

        // Clean up S3 files for uploaded documents before deleting the row.
        // DB cascade only handles DB rows — orphaned S3 objects would otherwise stay forever.
        $transaction->load('documents');
        foreach ($transaction->documents as $doc) {
            try {
                if ($doc->file_path) {
                    \Illuminate\Support\Facades\Storage::disk('s3')->delete($doc->file_path);
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to remove S3 file {$doc->file_path}: " . $e->getMessage());
            }
        }

        $code = $transaction->transaction_code;
        $transaction->delete();   // cascades: documents, transaction_logs, payments, property_maps (→ boundaries)

        return response()->json([
            'message' => "Transaction {$code} deleted.",
        ]);
    }

    /**
     * On-demand "Email Client" action from the transaction page.
     * Admin/staff types a subject + body + optional attachments → email is sent
     * to the transaction's client.
     */
    public function emailClient(Request $request, Transaction $transaction)
    {
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('staff') && !$user->hasRole('agent')) {
            abort(403);
        }
        // Staff can only email clients on transactions assigned to them
        if (!$user->hasRole('admin') && $transaction->assigned_staff_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'subject'         => 'required|string|max:140',
            'body'            => 'required|string|max:5000',
            'attachments'     => 'nullable|array|max:5',
            'attachments.*'   => 'file|max:10240|mimes:pdf,jpg,jpeg,png,gif,doc,docx,xls,xlsx',
        ]);

        $transaction->load('user');
        if (!$transaction->user?->email) {
            return response()->json(['message' => 'This transaction has no client email on file.'], 422);
        }

        // Read attachments into memory so they survive the temp-file lifecycle.
        $attachments = [];
        foreach ($request->file('attachments', []) as $file) {
            $attachments[] = [
                'data' => file_get_contents($file->getRealPath()),
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
            ];
        }

        try {
            Mail::to($transaction->user->email)
                ->send(new AdminMessageToClient(
                    $transaction,
                    $user,
                    $data['subject'],
                    $data['body'],
                    $attachments,
                ));
        } catch (\Throwable $e) {
            Log::error('Admin email to client failed: ' . $e->getMessage(), [
                'transaction_id' => $transaction->id,
                'sender_id'      => $user->id,
            ]);
            return response()->json(['message' => 'Failed to send email. Please try again.'], 500);
        }

        // Audit trail
        TransactionLog::create([
            'transaction_id' => $transaction->id,
            'performed_by'   => $user->id,
            'action'         => 'Email sent to client',
            'notes'          => $data['subject'] . (count($attachments) ? " · " . count($attachments) . " attachment(s)" : ''),
        ]);

        return response()->json([
            'message' => "Email sent to {$transaction->user->name}"
                . (count($attachments) ? ' with ' . count($attachments) . ' attachment(s)' : '')
                . '.',
        ]);
    }

    private function authorizeAccess($user, $transaction)
    {
        if ($user->hasRole('client') && $transaction->user_id !== $user->id) abort(403);
        if (($user->hasRole('staff') || $user->hasRole('agent')) && $transaction->assigned_staff_id !== $user->id) abort(403);
    }
}

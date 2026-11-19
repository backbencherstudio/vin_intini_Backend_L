<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    /**
     * Display a listing of contact messages with search, status filtering, summary metrics, and pagination.
     */
    public function getContactUs(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $status = strtolower(trim((string) $request->input('status', 'all')));
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        // Overall KPI metrics
        $summary = [
            'total_messages' => ContactUs::count(),
            'unread_messages' => ContactUs::where('status', ContactUs::STATUS_UNREAD)->count(),
            'read_messages' => ContactUs::where('status', ContactUs::STATUS_READ)->count(),
            'replied_messages' => ContactUs::where('status', ContactUs::STATUS_REPLIED)->count(),
            'today_messages' => ContactUs::whereDate('created_at', Carbon::today())->count(),
        ];

        $query = ContactUs::query()->latest('id');

        // Status Filter
        if ($status !== 'all' && in_array($status, [ContactUs::STATUS_UNREAD, ContactUs::STATUS_READ, ContactUs::STATUS_REPLIED], true)) {
            $query->where('status', $status);
        }

        // Search Filter: name, email, subject, phone, message, address
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('admin_note', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate($perPage);

        $data = $contacts->getCollection()->map(fn (ContactUs $contact, int $index): array => [
            'sl_no' => (($contacts->currentPage() - 1) * $contacts->perPage()) + $index + 1,
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'subject' => $contact->subject,
            'message' => $contact->message,
            'address' => $contact->address,
            'status' => $contact->status ?: ContactUs::STATUS_UNREAD,
            'admin_note' => $contact->admin_note,
            'created_at' => $contact->created_at?->toIso8601String(),
            'created_at_formatted' => $contact->created_at?->format('M d, Y h:i A'),
            'created_at_human' => $contact->created_at?->diffForHumans(),
            'read_at' => $contact->read_at?->toIso8601String(),
            'replied_at' => $contact->replied_at?->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact messages retrieved successfully.',
            'data' => $data,
            'summary' => $summary,
            'pagination' => [
                'current_page' => $contacts->currentPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
                'last_page' => $contacts->lastPage(),
                'from' => $contacts->firstItem(),
                'to' => $contacts->lastItem(),
            ],
        ]);
    }

    /**
     * Display the specified contact message and automatically mark it as read if unread.
     */
    public function show(int $id): JsonResponse
    {
        $contact = ContactUs::findOrFail($id);

        // Auto mark as read on view
        if ($contact->status === ContactUs::STATUS_UNREAD) {
            $contact->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact message retrieved successfully.',
            'data' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'subject' => $contact->subject,
                'message' => $contact->message,
                'address' => $contact->address,
                'status' => $contact->status,
                'admin_note' => $contact->admin_note,
                'created_at' => $contact->created_at?->toIso8601String(),
                'created_at_formatted' => $contact->created_at?->format('M d, Y h:i A'),
                'created_at_human' => $contact->created_at?->diffForHumans(),
                'read_at' => $contact->read_at?->toIso8601String(),
                'replied_at' => $contact->replied_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Manually toggle or update the status of a contact message (unread, read, replied).
     */
    public function markStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:unread,read,replied'],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $contact = ContactUs::findOrFail($id);
        $newStatus = $validated['status'];

        if ($newStatus === ContactUs::STATUS_READ) {
            $contact->markAsRead();
        } elseif ($newStatus === ContactUs::STATUS_REPLIED) {
            $contact->markAsReplied();
        } else {
            $contact->markAsUnread();
        }

        if (array_key_exists('admin_note', $validated)) {
            $contact->update(['admin_note' => $validated['admin_note']]);
        }

        return response()->json([
            'success' => true,
            'message' => "Message marked as {$newStatus}.",
            'data' => [
                'id' => $contact->id,
                'status' => $contact->status,
                'admin_note' => $contact->admin_note,
                'read_at' => $contact->read_at?->toIso8601String(),
                'replied_at' => $contact->replied_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update internal admin note for a contact message.
     */
    public function updateAdminNote(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $contact = ContactUs::findOrFail($id);
        $contact->update([
            'admin_note' => $validated['admin_note'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin note updated successfully.',
            'data' => [
                'id' => $contact->id,
                'admin_note' => $contact->admin_note,
                'status' => $contact->status,
            ],
        ]);
    }

    /**
     * Delete a contact message from the database.
     */
    public function destroy(int $id): JsonResponse
    {
        $contact = ContactUs::findOrFail($id);
        $senderName = $contact->name;
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => "Message from '{$senderName}' deleted successfully.",
        ]);
    }
}

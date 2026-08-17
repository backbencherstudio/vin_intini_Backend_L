<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    public function getContactUs(Request $request)
    {
        $search = $request->input('search');

        $contacts = ContactUs::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->input('per_page', 20));

        $data = $contacts->getCollection()->map(function ($contact, $index) use ($contacts) {
            return [
                'sl_no' => (($contacts->currentPage() - 1) * $contacts->perPage()) + $index + 1,
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'subject' => $contact->subject,
                'message' => $contact->message,
                'address' => $contact->address,
            ];
        });

        return response()->json([
            'success' => true,

            'data' => $data,

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
}

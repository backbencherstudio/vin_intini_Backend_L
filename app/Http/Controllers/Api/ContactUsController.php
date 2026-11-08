<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use App\Notifications\ContactUsAutoResponseNotification;
use App\Notifications\ContactUsNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class ContactUsController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $contact = ContactUs::create($request->all());

        // Notification to admin
        Notification::route('mail', 'contact@mindunite.com')->notify(new ContactUsNotification($contact));

        // auto response to user
        Notification::route('mail', $contact->email)->notify(new ContactUsAutoResponseNotification($contact));

        return response()->json([
            'status' => 'success',
            'message' => 'Your message has been received. We will contact you soon!',
            'data' => $contact,
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactUsAutoResponseMail;
use App\Mail\ContactUsNotificationMail;
use App\Models\ContactUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactUsController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $contact = ContactUs::create($request->all());

        // Mail::to('niaz.softvence@gmail.com')->send(new ContactUsNotificationMail($contact));
        Mail::to('niaz@softvencedelta.com')->send(new ContactUsNotificationMail($contact));

        Mail::to($contact->email)->send(new ContactUsAutoResponseMail($contact));

        return response()->json([
            'status'  => 'success',
            'message' => 'Your message has been received. We will contact you soon!',
            'data'    => $contact
        ], 201);
    }
}

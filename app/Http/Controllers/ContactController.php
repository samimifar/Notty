<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class ContactController extends Controller
{

    public function contacts()
    {
        $contacts = Contact::where('user_id', Auth::id())->get();
        return response()->json($contacts);
    }


    public function index()
    {
        return view('contacts.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone_number' => 'required|string|max:32',
        ]);

        $uid = Auth::id();

        // 1) The phone must belong to an existing user
        $contactUser = User::where('phone_number', $request->phone_number)->first();
        if (!$contactUser) {
            return response()->json([
                'errors' => ['phone_number' => ['User with this phone not found.']],
            ], 422);
        }

        if ($contactUser->id === $uid) {
            return response()->json([
                'errors' => ['phone_number' => ["You can't add yourself as a contact."]],
            ], 422);
        }

        $exists = Contact::where('user_id', $uid)
            ->where(function ($q) use ($request, $contactUser) {
                $q->where('phone_number', $request->phone_number);
            })->exists();

        if ($exists) {
            return response()->json([
                'errors' => ['phone_number' => ['This contact already exists.']],
            ], 422);
        }

        $contact = Contact::create([
            'user_id'    => $uid,
            'name'       => $request->name,
            'phone_number'      => $request->phone_number,
        ]);

        return response()->json($contact, 201);
    }


    public function show(Contact $contact)
    {
        
        return response()->json($contact);
    }


    public function update(Request $request, Contact $contact)
    {

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $contact->update($request->only('name', 'phone_number'));

        return response()->json($contact);
    }


    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->json(null, 204);
    }
}

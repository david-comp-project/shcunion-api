<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;


class RegistrationController extends Controller
{



    public function signup (StoreRegistrationRequest $request) {

        try {
            $validatedData = $request->validated();

            $validatedData['full_name'] = $validatedData['first_name'] . ' ' . $validatedData['last_name'];
            $validatedData['social_media'] = json_encode([
                'facebook' => '',
                'twitter'  => '',
                'instagram'=> '',
                'linkedin' => '',
                'github'   => '',
                'medium'   => '',
            ]);
            $validatedData['profile_picture'] = 'profile/user-default.png'; // Sesuaikan path gambar default

            $user = User::create($validatedData);

            $user->sendEmailVerificationNotification();

            $user->increment('total_points', 10);

            $user['badge'] = $user->getBadge()->value;

            // $user->assignRole('active');

            $user->assignRole('active');

            if ($user) {
                return response()->json([
                    'message' => 'Registration successful!',
                    'user' => $user
                ], 201);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(), // Pesan error dikembalikan dalam bentuk array
            ], 422);
        }
       
    }
}
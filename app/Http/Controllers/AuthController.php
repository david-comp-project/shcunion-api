<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use App\Traits\HasFileTrait;
use Illuminate\Http\Request;
use App\Jobs\ResetPasswordJob;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTFactory;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use App\Notifications\ResetPasswordNotification;

class AuthController extends Controller
{
    use HasFileTrait;

    // Redirect ke provider SSO (Google, Facebook, dll.)
    public function redirectToProvider($provider)
    {
          // Log::info('redirect', [Socialite::driver($provider)->stateless()->redirect()->getTargetUrl()]);
        return [
            "redirect_url" => Socialite::driver($provider)->stateless()->redirect()->getTargetUrl()
        ];
    }
    
    public function handleProviderCallback($provider)
    {
        try {

            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Log::info('Informasi callback user:', ['user' => $socialUser]);

            // Cek apakah user sudah ada berdasarkan provider_id & provider_name
            $user = User::where('provider_id', $socialUser->getId())
                        ->where('provider_name', $provider)
                        ->first();

            // Jika user belum ada, cek berdasarkan email
            if (!$user) {
                $user = User::where('email', $socialUser->getEmail())->first();

                if ($user) {
                    // Update user dengan provider baru jika email sudah ada
                    $user->update([
                        'provider_id' => $socialUser->getId(),
                        'provider_name' => $provider,
                    ]);
                } else {
                    // Jika tidak ada, buat user baru
                    // Log::info('data callback : ', [$socialUser->getName()]);

                    // Memisahkan nama lengkap berdasarkan spasi
                    $fullName = explode(' ', $socialUser->getName());

                    // Ambil firstName dari kata pertama
                    $firstName = $fullName[0];

                    // Ambil lastName dengan menggabungkan seluruh nama setelah firstName
                    $lastName = implode(' ', array_slice($fullName, 1)); // Menggabungkan sisa nama sebagai lastName

                    // Jika tidak ada, buat user baru
                    $user = User::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'full_name' => $firstName . ' ' . $lastName, // Gabungkan first_name dan last_name dengan spasi
                        'email' => $socialUser->getEmail(),
                        'provider_id' => $socialUser->getId(),
                        'provider_name' => $provider,
                        'password' => bcrypt(Str::random(16)), // Password acak
                        'status' => 'active',
                        'social_media' =>  json_encode([
                                                        'facebook' => '',
                                                        'twitter'  => '',
                                                        'instagram'=> '',
                                                        'linkedin' => '',
                                                        'github'   => '',
                                                        'medium'   => '',
                                                    ])
                    ]);

                    

                    $user->assignRole('active');
                }
            }

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            $payload = JWTFactory::sub($user->user_id)
                        ->exp(now()->addDays(7)->timestamp) // Expired 7 hari
                        ->make();

            $refreshToken = JWTAuth::encode($payload); // Pastikan string

            $userId = $user->user_id;

            // Buat URL redirect dengan parameter yang benar
            $url = config('app.frontend_url') . "/auth/callback?"
                    . "user_id=" . rawurlencode($userId)
                    . "&access_token=" . rawurlencode($token)
                    . "&refresh_token=" . rawurlencode($refreshToken);

            // Log URL sebelum redirect (untuk debugging)
            // Log::info('Redirecting to frontend:', ['url' => $url]);

            // Redirect ke frontend dengan token
            return redirect()->away($url);


        } catch (\Exception $e) {
            return response()->json(['error' => 'Login failed!'], 401);
        }
    }



    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                "status" => 401,
                "message" => "Some Validation Error Detected",
                "data" => $validator->errors(),
            ], 403);
        }
    
        $credentials = $validator->validated();
        
        if (!User::where('email', $credentials['email'])->exists()) {

            return response()->json([
                'status' => 404,
                'message' => 'Akun Tidak Ditemukan'
            ], 404);
        }

        if (!$token = Auth('api')->attempt($credentials)) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized'
            ], 403);
        }
    
        $user = Auth('api')->user();
        
        // Generate Refresh Token
        $refreshToken = Auth('api')->setTTL(20160)->attempt($credentials); // 14 hari

        $user['profile_picture'] = $this->getUrlFile($user['profile_picture']);
        $user['profile_cover'] = $this->getUrlFile($user['profile_cover']);
        $user['badge'] = $user->getBadge()->value;
        $user['badge_color'] = $user->getBadgeColor();
        $user['suspended_time'] = $user->suspended_date ? (int) Carbon::parse($user->suspended_date)->diffInDays(now()) * -1: null; // Hitung hari tersisa

    
        return response()->json([
            'success' => true,
            'message' => "User Login Successfully",
            'user' => $user,
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray()
        ], 200);
    }
    

    public function logout () {

        Auth('api')->logout(true);

        return response()->json(['message' => 'Successfully logged out']);

    }

    public function me() {
        $user = Auth('api')->user();

        return response()->json([
            "status" => 200,
            "user" => $user
        ],200);
    }

    public function sendVerify()
    {
        $user = auth('api')->user();
    
        if ($user) { // Mengecek apakah user ada
            $user->sendEmailVerificationNotification();
    
            return response()->json([
                'message' => 'Email Verification Has Been Sent'
            ], 200);
        }
    
        return response()->json([
            'message' => 'User Does not Exist'
        ], 404);
    }

    public function verify($userId, $hash)
    {
        $user = User::findOrFail($userId);
    
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json([
                'message' => 'Invalid verification link.'
            ], 403);
        }
    
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.'
            ], 200);
        }
    
        $user->markEmailAsVerified();
        // Opsional: Fire event Verified jika diperlukan
        // event(new \Illuminate\Auth\Events\Verified($user));

        // $user->status = 'verified';
        $user->save();
    
        return response()->json([
            'message' => 'Email successfully verified.',
            'user' => $user
        ], 200);
    }
    
    public function changePassword(Request $request) {
        $user = Auth('api')->user(); // Mendapatkan user yang sedang login
    
        // Validasi input
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
    
        // Cek apakah password lama benar
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Password lama salah'], 400);
        }
    
        // Update password baru
        $user->password = Hash::make($request->new_password);
        $user->save();
    
        return response()->json(['message' => 'Password berhasil diubah'], 200);
    }

    public function sendResetPassword(Request $request){
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
    
        $token = Password::broker()->createToken($user);
        dispatch(new ResetPasswordJob($user, $token));
        
        return response()->json(['message' => 'Reset password link sent.']);

    }
    
    public function resetPassword(Request $request)
    {
        // Validasi input
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed', // Harus dikonfirmasi dengan password_confirmation
        ]);
    
        // Gunakan Laravel Password Broker untuk memproses reset password
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );
    
        // Berikan respons berdasarkan status
        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password has been reset successfully.'], 200);
        } else {
            return response()->json(['message' => 'Invalid token or email.'], 400);
        }
    }
    

    public function refresh(Request $request)
    {
        try {
            $newToken = Auth('api')->refresh(); // Refresh access token
            
            return response()->json([
                'access_token' => $newToken,
                'refresh_token' => $request->header('Authorization'), // Kirim ulang refresh token
                'token_type' => 'bearer',
                'expires_in' => Auth('api')->factory()->getTTL() * 60
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Refresh token invalid'], 401);
        }
    }
    
    
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Helpers\ApiFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $params = $request->all();
            $validator = Validator::make(
                $params,
                [
                    'email' => 'required|email',
                    'password' => 'required|min:4',
                ],
                [
                    'email.required' => 'Email is required',
                    'email.email' => 'Email must be a valid email address',
                    'password.required' => 'Password is required',
                    'password.min' => 'Password must be at least :min characters',
                ]
            );

            if ($validator->fails()) {
                return response()->json(ApiFormatter::createJson(400, 'Bad Request', $validator->errors()->all()), 400);
            }

            // Cari user berdasarkan email
            $user = User::where('email', $params['email'])->first();

            if (!$user) {
                return response()->json(ApiFormatter::createJson(404, 'Account not found'), 404);
            }

            // Periksa password
            if (!Hash::check($params['password'], $user->password)) {
                return response()->json(ApiFormatter::createJson(401, 'Password does not match'), 401);
            }

            // Generate token JWT
            if (!$istoken = JWTAuth::fromUser($user)) {
                return response()->json(ApiFormatter::createJson(500, 'Failed to generate token'), 500);
            }

            // Informasi token
            $currentDateTime = Carbon::now();
            $expirationDateTime = Carbon::createFromTimestamp(JWTAuth::setToken($istoken)->getPayload()->get('exp'));

            $info = [
                'type' => 'Bearer',
                'token' => $istoken,
                'expires' => $expirationDateTime->format('Y-m-d H:i:s')
            ];

            return response()->json(ApiFormatter::createJson(200, 'Login successful', $info), 200);
        } catch (\Exception $e) {
            return response()->json(ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage()), 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $params = $request->all();
            $validator = Validator::make(
                $params,
                [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|string|min:6|confirmed',
                ],
                [
                    'name.required' => 'Name is required',
                    'email.required' => 'Email is required',
                    'email.email' => 'Email must be a valid email address',
                    'email.unique' => 'Email is already in use',
                    'password.required' => 'Password is required',
                    'password.min' => 'Password must be at least :min characters',
                    'password.confirmed' => 'Password confirmation does not match',
                ]
            );

            if ($validator->fails()) {
                return response()->json(ApiFormatter::createJson(400, 'Bad Request', $validator->errors()->all()), 400);
            }

            // Create the user
             User::create([
                'name' => $params['name'],
                'email' => $params['email'],
                'password' => Hash::make($params['password']),
            ]);

            // Respond with success
            return response()->json(ApiFormatter::createJson(201, 'Registration successful'), 201);
        } catch (\Exception $e) {
            return response()->json(ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage()), 500);
        }
    }

    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $token = JWTAuth::getToken();
            $payload = JWTAuth::getPayload($token);

            $expiration = $payload->get('exp');
            $expiration_time = date('Y-m-d H:i:s', $expiration);

            $data['name'] = $user['name'];
            $data['email'] = $user['email'];
            $data['exp'] = $expiration_time;

            return response()->json(ApiFormatter::createJson(200, 'Logged in User', $data), 200);
        } catch (\Exception $e) {
            return response()->json(ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage()), 500);
        }
    }

    public function refresh()
    {
        $newToken = JWTAuth::refresh(JWTAuth::getToken());

        $currentDateTime = Carbon::now();
        $expiredDateTime = $currentDateTime->addMinutes(JWTAuth::factory()->getTTL() * 60);

        $info = [
            'type' => 'Bearer',
            'token' => $newToken,
            'expires' => $expiredDateTime->format('Y-m-d H:i:s'),
        ];

        return response()->json(ApiFormatter::createJson(200, 'Refresh Successfully', $info), 200);
    }


    public function logout()
    {
        auth()->guard('api')->logout();
        return response()->json(ApiFormatter::createJson(200, 'Logout Successfully'), 200);
    }
}

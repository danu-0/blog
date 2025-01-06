<?php

namespace App\Http\Middleware;

use App\Helpers\ApiFormatter;
use App\Models\LogModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Throwable;

class LogAPI
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = null;
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            $user = null;
        }

        $filteredRequest = ApiFormatter::filterSensitiveData($request->all());
        $log = LogModel::create([
            'user_id' => $user ? $user->id : null,
            'log_method' => $request->method(),
            'log_url' => $request->fullUrl(),
            'log_ip' => $request->ip(),
            'log_request' => json_encode($filteredRequest),
        ]);

        try {
            $response = $next($request);
            $log->update([
                'log_response' => $response->getContent(),
            ]);

            return $response;
        } catch (Throwable $e) {
            $erroResponse = ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage());
            $log->update([
                'log_response' => response()->json($erroResponse, 500),
            ]);

            return response()->json($erroResponse, 500);
        }
    }
}

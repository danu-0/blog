<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('me',                [AuthController::class, 'me']);
    Route::get('refresh',           [AuthController::class, 'refresh']);
    Route::get('logout',            [AuthController::class, 'logout']);

    Route::prefix('/post')->group(function () {
        Route::get('', [PostController::class, 'index']);
        Route::post('', [PostController::class, 'create']);
        Route::get('/{userId}', [PostController::class, 'indexByUser']);
        Route::put('/{postId}', [PostController::class, 'update']);
        Route::patch('/{postId}', [PostController::class, 'patch']);
        Route::delete('/{postId}', [PostController::class, 'delete']);

        // Routes for Comments
        Route::post('/{postId}/comment', [PostController::class, 'addComment']);
        Route::get('/{postId}/comments', [PostController::class, 'getComments']);

        // Routes for Likes
        Route::post('/{postId}/like', [PostController::class, 'toggleLike']);
        Route::get('/{postId}/likes', [PostController::class, 'getLikeCount']);
    });
});

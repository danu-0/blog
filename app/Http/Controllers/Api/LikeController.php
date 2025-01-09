<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\Like;
use Illuminate\Support\Facades\Auth;
use Throwable;

class LikeController extends Controller
{
    public function create($postId)
    {
        try {
            $like = Like::where('post_id', $postId)->where('user_id', Auth::id())->first();
            if ($like) {
                $like->delete();
                $response = ApiFormatter::createJson(200, 'Post unliked successfully');
                return response()->json($response, 200);
            } else {
                $data = [
                    'post_id' => $postId,
                    'user_id' => Auth::id(),
                ];
                $newLike = Like::create($data);
                $response = ApiFormatter::createJson(201, 'Post liked successfully!', $newLike);
                return response()->json($response, 201);
            }
        } catch (Throwable $e) {
            $response = ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage());
            return response()->json($response, 500);
        }
    }

    public function index($postId)
    {
        try {
            $likeCount = Like::where('post_id', $postId)->count();
            $likes = ['like_count' => $likeCount];
            $response = ApiFormatter::createJson(200, 'Like count retrieved successfully', $likes);
            return response()->json($response, 200);
        } catch (Throwable $e) {
            $response = ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage());
            return response()->json($response, 500);
        }
    }
}

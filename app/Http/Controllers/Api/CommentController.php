<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CommentController extends Controller
{
    public function index($postId)
    {
        try {
            $comments = Comment::where('post_id', $postId)->with('user')->latest()->get();
            $response = ApiFormatter::createJson(200, 'Comments retrieved successfully', $comments);
            return response()->json($response, 200);
        } catch (Throwable $e) {
            $response = ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage());
            return response()->json($response, 500);
        }
    }


    public function create(Request $request, $postId)
    {
        try {
            $params = $request->all();
            $validator = Validator::make(
                $params,
                [
                    'comment' => 'required',
                ],
                [
                    'comment.required' => 'comment is required',
                ]
            );
            if ($validator->fails()) {
                $response = ApiFormatter::createJson(400, 'Bad Request', $validator->errors()->all());
                return response()->json($response, 400);
            }

            $comment = [
                'post_id' => $postId,
                'user_id' => Auth::id(),
                'comment' => $params['comment'],
            ];

            $data = Comment::create($comment);
            $response = ApiFormatter::createJson(201, 'Comment created successfully!', $data);
            return response()->json($response, 201);
        } catch (Throwable $e) {
            $response = ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage());
            return response()->json($response, 500);
        }
    }
}

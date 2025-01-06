<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user')->latest()->get();
        if (is_null($posts)) {
            $response = ApiFormatter::createJson(404, 'Post not found');
            return response()->json($response, 404);
        }
        $res = ApiFormatter::createJson(200, 'Get all post successfully', $posts);
        return response()->json($res, 200);
    }

    public function indexByUser($userId)
    {
        $user = User::find($userId);
        $posts = Post::where('user_id', $userId)->with('user')->latest()->get();
        if (is_null($user)) {
            $response = ApiFormatter::createJson(404, 'User not found');
            return response()->json($response, 404);
        }
        $response = ApiFormatter::createJson(200, 'Get all post by user successfully', $posts);
        return response()->json($response, 200);
    }


    public function create(Request $request)
    {
        try {
            $params = $request->all();
            $validator = Validator::make(
                $params,
                [
                    'title' => 'required|max:255',
                    'content' => 'required',
                ],
                [
                    'title.required' => 'post title is required',
                    'title.max' => 'post title max 255 characters',
                    'content.required' => 'post content is required'
                ]
            );
            if ($validator->fails()) {
                $response = ApiFormatter::createJson(400, 'Bad Request', $validator->errors()->all());
                return response()->json($response, 400);
            }

            $posts = [
                'user_id' => Auth::id(),
                'post_title' => $params['title'],
                'post_content' => $params['content'],
            ];

            $data = Post::create($posts);
            $response = ApiFormatter::createJson(201, 'Post created successfully!', $data);
            return response()->json($response, 201);
        } catch (Throwable $e) {
            $response = ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage());
            return response()->json($response, 500);
        }
    }

    public function update(Request $request, Post $postId)
    {
        try {
            if (Auth::id() !== $postId->user_id) {
                $response = ApiFormatter::createJson(403, 'User unauthorized');
                return response()->json($response, 403);
            }
            $params = $request->all();
            $validator = Validator::make(
                $params,
                [
                    'title' => 'required|max:255',
                    'content' => 'required',
                ],
                [
                    'title.required' => 'post title is required',
                    'title.max' => 'post title max 255 characters',
                    'content.required' => 'post content is required'
                ]
            );
            if ($validator->fails()) {
                $response = ApiFormatter::createJson(400, 'Bad Request', $validator->errors()->all());
                return response()->json($response, 400);
            }

            $posts = [
                'post_title' => $params['title'],
                'post_content' => $params['content'],
            ];

            $postId->update($posts);
            $updatedPost = $postId->fresh();
            $response = ApiFormatter::createJson(200, 'Post updated successfully!', $updatedPost);
            return response()->json($response, 200);
        } catch (Throwable $e) {
            $response = ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage());
            return response()->json($response, 500);
        }
    }



    public function patch(Request $request, Post $postId)
    {
        try {
            if (Auth::id() !== $postId->user_id) {
                $response = ApiFormatter::createJson(403, 'User unauthorized');
                return response()->json($response, 403);
            }
            $params = $request->all();
            if (isset($params['title'])) {
                $validator = Validator::make(
                    $params,
                    [
                        'title' => 'required|max:255',
                    ],
                    [
                        'title.required' => 'post title is required',
                        'title.max' => 'post title max 255 characters',
                    ]
                );
                if ($validator->fails()) {
                    $response = ApiFormatter::createJson(400, 'Bad Request', $validator->errors()->all());
                    return response()->json($response, 400);
                }
                $data['post_title'] = $params['title'];
            }

            if (isset($params['content'])) {
                $validator = Validator::make(
                    $params,
                    [
                        'content' => 'required',
                    ],
                    [
                        'content.required' => 'post content is required'
                    ]
                );
                if ($validator->fails()) {
                    $response = ApiFormatter::createJson(400, 'Bad Request', $validator->errors()->all());
                    return response()->json($response, 400);
                }
                $data['post_content'] = $params['content'];
            }
            $postId->update($data);
            $updatedPost = $postId->fresh();
            $response = ApiFormatter::createJson(200, 'Post updated successfully!', $updatedPost);
            return response()->json($response, 200);
        } catch (Throwable $e) {
            $response = ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage());
            return response()->json($response, 500);
        }
    }


    public function delete(Post $postId)
    {
        try {
            if (Auth::id() !== $postId->user_id) {
                $response = ApiFormatter::createJson(403, 'User unauthorized');
                return response()->json($response, 403);
            }

            $postId->delete();
            $response = ApiFormatter::createJson(200, 'Post deleted successfully!');
            return response()->json($response, 200);
        } catch (Throwable $e) {
            $response = ApiFormatter::createJson(500, 'Internal Server Error', $e->getMessage());
            return response()->json($response, 500);
        }
    }

    //comments
    public function addComment(Request $request, $postId)
    {
    $validated = $request->validate([
        'comment' => 'required|string',
    ]);

    $comment = Comment::create([
        'post_id' => $postId,
        'user_id' => Auth::id(),
        'comment' => $validated['comment'],
    ]);

    return response()->json(ApiFormatter::createJson(201, 'Comment added successfully!', $comment), 201);
    }


    public function getComments($postId)
    {
    $comments = Comment::where('post_id', $postId)->with('user')->latest()->get();

    return response()->json(ApiFormatter::createJson(200, 'Comments retrieved successfully', $comments), 200);
    }

    //like and un like post
    public function toggleLike($postId)
    {
    $like = Like::where('post_id', $postId)->where('user_id', Auth::id())->first();

    if ($like) {
        $like->delete();
        return response()->json(ApiFormatter::createJson(200, 'Post unliked successfully'), 200);
    } else {
        $newLike = Like::create([
            'post_id' => $postId,
            'user_id' => Auth::id(),
        ]);
        return response()->json(ApiFormatter::createJson(201, 'Post liked successfully!', $newLike), 201);
    }
    }

    //see qtt comment
    public function getLikeCount($postId)
    {
    $likeCount = Like::where('post_id', $postId)->count();

    return response()->json(ApiFormatter::createJson(200, 'Like count retrieved successfully', ['like_count' => $likeCount]), 200);
    }



}

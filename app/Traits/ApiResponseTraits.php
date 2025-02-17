<?php

namespace App\Traits;

trait ApiResponseTrait
{
    /**
     * Format sukses response dengan data
     */
    public function successResponse($message, $data = [], $status = 200)
    {
        return response()->json([
            'status' => $status,
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Format error response
     */
    public function errorResponse($message, $status = 400)
    {
        return response()->json([
            'status' => $status,
            'success' => false,
            'message' => $message,
            'data' => []
        ], $status);
    }

    /**
     * Format response untuk story detail
     */
    public function formatStoryResponse($story)
    {
        return [
            'id' => $story->id,
            'title' => $story->title,
            'cover' => $story->cover,
            'created_at' => $story->created_at->format('Y-m-d H:i:s'),
            'category' => $story->category->name ?? null,
            'images' => $story->story_images->pluck('image_path') ?? [],
            'content' => $story->content,
            'author_id' => $story->user->id ?? null,
            'author_name' => $story->user->username ?? null,
            'author_image' => $story->user->image ?? null,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;

class StoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $story = Story::all();
        return  response()->json([
            'message' => 'Successfully Display Data',
            'success' => true,
            'data' => $story
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validateData = $request->validate([
                'title' => 'required|unique:stories,title',
                'content' => 'required|string|max:1000',
                'category_id' => 'required|exists:categories,id',
                'cover' => 'nullable|image|mimes:jpg,png,svg,gif,webp|max:2048',
            ]);

            $user = $request->user();
            $validateData['user_id'] = $user->id;

            $newStory = Story::create($validateData);
            return response()->json([
                'message' => "Added Story",
                'success' => true,
                'data' => $newStory
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->errors(),
                'success' => false
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Created Data',
                'success' => false,
                'errors' => $e->getMessage()
            ], 500);
        }
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $story = Story::find($id);
        if ($story) {
            return response()->json([
                'message' => 'Successfully Display Data',
                'success' => true,
                'data' => $story
            ]);
        } else {
            return response()->json([
                'message' => 'Story Not Found',
                'success' => false
            ], 404);
        }
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Story $story)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'User Not Authenticated',
                'success' => false
            ], 404);
        }
        $story = Story::find($id);
        if (!$story) {
            return response()->json([
                'message' => 'Story not found',
                'success' => false,
            ], 404);
        }
        try {
            $validateData = $request->validate([
                'title' => 'required|unique:stories,title',
                'content' => 'required|max:1000',
                'category_id' => 'required|exists:categories,id',
                'cover' => 'required|image|mimes:jpg,png,svg,gif,webp|max:2018'
            ]);
            $story->update($validateData);
            return response()->json([
                'message' => 'Updated Success',
                'success' => true
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->errors(),
                'status' => false
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Update Data',
                'status' => false
            ], 500);
            //throw $th;
        }
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $story = Story::find($id);
        if (!$story) {
            return response()->json([
                'message' => 'Story Not Found',
                'success' => false
            ], 404);
        }
        $story->delete();
        return response()->json([
            'message' => 'deleted Success',
            'success' => true
        ]);
        //
    }
    public function getStoryUser(Request $request)
    {
        $user = $request->user(); // Mendapatkan user yang sedang login dari token

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated',
                'success' => false,
            ], 401);
        }

        // Mendapatkan semua cerita yang dibuat oleh pengguna saat ini
        $stories = Story::where('user_id', $user->id)->get();

        if ($stories->isEmpty()) {
            return response()->json([
                'message' => 'No stories found for this user',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'message' => 'Successfully retrieved stories',
            'success' => true,
            'data' => $stories,
        ], 200);
    }
}

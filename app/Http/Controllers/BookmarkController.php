<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookmarkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }
    public function Bookmark(Request $request)
    {
        try {
            $validateData = $request->validate([
                'story_id' => 'required|exists:stories,id',
                // 'user_id' => 'required|exists:users,id'
            ]);
            $user = $request->user();
            $validateData['user_id'] = $user->id;
            // $newBookmark = Bookmark::create($validateData);

            $bookmark = Bookmark::where([
                'user_id' => $validateData['user_id'],
                'story_id' => $validateData['story_id'],
            ])->first();

            if ($bookmark) {
                $bookmark->delete();
                return response()->json([
                    'message' => 'Successfully Deleted Bookmark .',
                    'success' => true,
                ], 200);
            } else {
                $newBookmark = Bookmark::create($validateData);
                $story = Story::find($validateData['story_id']);
                return response()->json([
                    'message' => 'Bookmark Added.',
                    'success' => true,
                    'data' => [
                        'bookmark' => [
                            'id' => $newBookmark->id,
                            'user_id' => $newBookmark->user_id,
                            'username' => $user->username,
                        ],
                        'story' => [
                            'story_id' => $newBookmark->story_id,
                            'story_name' => $story->title,
                            'created_story' => $story->created_at,
                            'category' => $story->category->name,
                            'story_author_id' => $story->user_id,
                            'author_name' => $story->user->username,
                            'image' => $story->user->image,
                            'content' => $story->content
                        ]
                    ]
                ], 200);
            }
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
            $validateData = $request->validate(['story_id' => 'required|exists:stories,id']);
            $user = $request->user();
            $validateData['user_id'] = $user->id;
            $newBookmark = Bookmark::create($validateData);
            $story = Story::find($validateData['story_id']);

            return response()->json([
                'message' => 'Added Bookmark',
                'success' => true,
                'data' => [
                    'bookmark' => [
                        'id' => $newBookmark->id,
                        'user_id' => $newBookmark->user_id,
                        'username' => $user->username,
                    ],
                    'story' => [
                        'story_id' => $newBookmark->story_id,
                        'story_name' => $story->title,
                        'created_story' => $story->created_at,
                        'category' => $story->category->name,
                        'story_author_id' => $story->user_id,
                        'author_name' => $story->user->username,
                        'content' => $story->content,
                        'image' => $story->user->image
                    ]



                ]
            ]);
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
    public function show(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorisize', 'status' => false], 402);
        }
        $bookmark = Bookmark::where('user_id', $user->id)->with('story.user')->get();
        if ($bookmark->isEmpty()) {
            return response()->json(['message' => 'No Bookmark Found for this user', 'success' => false], 404);
        }
        $data = $bookmark->map(function ($bookmark) {
            $story = $bookmark->story;

            // Pastikan story dan relasi terkait tidak null
            if (!$story || !$story->user || !$story->category) {
                return null; // Abaikan bookmark yang tidak valid
            }
            return [
                'id' => $bookmark->id,
                'bookmark_user_id' => $bookmark->user_id,
                'bookmark_username' => $bookmark->user->username,
                'story_id' => $bookmark->story_id, // 
                'story_name' => $bookmark->story->title,
                'created_story' => $bookmark->story->created_at,
                'category' => $bookmark->story->category->name,
                'story_creator_user_id' =>
                $bookmark->story->user_id,
                'story_creator_username' => $bookmark->story->user->username
            ];
        });
        return response()->json([
            'message' => 'Display Bookmark',
            "success" => true,
            'data' => $data
        ]);

        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bookmark $bookmark)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bookmark $bookmark)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $bookmark = Bookmark::find($id);
        if (!$bookmark) {
            return response()->json(['message' => 'Bookmark Not Found', 'success' => false], 404);
        }
        $bookmark->delete();
        return response()->json(['message' => 'deleted Success', 'success' => true]);
        //
    }
}

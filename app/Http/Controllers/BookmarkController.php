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
            ]);
            $user = $request->user();
            $validateData['user_id'] = $user->id;

            $bookmark = Bookmark::where([
                'user_id' => $validateData['user_id'],
                'story_id' => $validateData['story_id'],
            ])->first();

            if ($bookmark) {
                $bookmark->delete();
                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Successfully Deleted Bookmark .',
                ], 200);
            } else {
                $newBookmark = Bookmark::create($validateData);
                $story = Story::find($validateData['story_id']);
                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Bookmark Added.',
                    'data' => [
                        'bookmark' => [
                            'id' => $newBookmark->id,
                            'user_id' => $newBookmark->user_id,
                            'username' => $user->username,
                        ],
                        'story' => [
                            'id' => $newBookmark->story_id,
                            'title' => $story->title,
                            'created_at' => $story->created_at,
                            'category' =>
                            $story->category->name,
                            'content' => $story->content,
                            'author' => $story->user ? [
                                'author_id' => $story->user_id,
                                'author_name' => $story->user->username,
                                'image' => $story->user->image,
                            ] : null,
                        ]
                    ]
                ], 200);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'success' => false,
                'message' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Error Created Data',
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
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $pagination = $request->query('per_page', 5);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 402,
                'success' => false,
                'message' => 'Unauthorized',

            ], 402);
        }

        $bookmarkQuery = Bookmark::where('user_id', $user->id)
            ->with('story.user', 'story.category');

        $bookmarksPaginated = $bookmarkQuery->paginate($pagination);

        if ($bookmarksPaginated->isEmpty()) {
            return response()->json(['message' => 'No Bookmark Found for this user', 'success' => false], 404);
        }

        $formattedBookmarks = $bookmarksPaginated->getCollection()->map(function ($bookmark) {
            $story = $bookmark->story;

            if (!$story || !$story->user || !$story->category) {
                return null;
            }
            return [
                'bookmark' => [
                    'id' => $bookmark->id,
                    'user_id' => $bookmark->user_id,
                    'username' => $bookmark->user->username,
                ],
                'story' => [
                    'id' => $story->id,
                    'title' => $story->title,
                    'cover' => $story->cover,
                    'created_at' => $story->created_at,
                    'category' => $story->category->name,
                    'author' => [
                        'author_id' => $story->user_id,
                        'author_name' => $story->user->username,
                        'author_image' => $story->user->image,
                    ]
                ],
            ];
        })->filter()->values();
        $bookmarksPaginated->setCollection($formattedBookmarks);

        return response()->json([
            'status' => 200,
            "success" => true,
            'message' => 'Display Bookmark Successfully',
            'data' => $bookmarksPaginated
        ]);
    }
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
    public function destroy($id) {}
}

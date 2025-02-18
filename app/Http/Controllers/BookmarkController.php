<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Traits\ApiResponseTrait;

class BookmarkController extends Controller
{
    use ApiResponseTrait;
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
                return $this->successResponse("Bookmark Deleted", 200);
            } else {
                $newBookmark = Bookmark::create($validateData);
                $story = Story::find($validateData['story_id']);
                return $this->successResponse("Bookmark Added", ['id' => $newBookmark->id, 'user_id' => $newBookmark->user_id, 'username' => $newBookmark->username, 'story' => $this->formatStoryResponse($story)]);
            }
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse("Error Creating Data: " . $e->getMessage(), 500);
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
    // public function show(Request $request)
    // {
    //     $pagination = $request->query('per_page', 10);
    //     $user = $request->user();

    //     if (!$user) {
    //         return $this->errorResponse('Unauthorized', 401);
    //     }

    //     $bookmarksPaginated = Bookmark::where('user_id', $user->id)
    //         ->with(['story.user', 'story.category'])
    //         ->paginate($pagination);

    //     if ($bookmarksPaginated->isEmpty()) {
    //         return $this->successResponse("No Story Data", null);
    //     }

    //     // Menggunakan trait formatStoryResponse
    //     $formattedBookmarks = $bookmarksPaginated->getCollection()->map(function ($bookmark) {
    //         return [
    //             'id' => $bookmark->id,
    //             'user_id' => $bookmark->user_id,
    //             'username' => $bookmark->user->username,
    //             'story' => $this->formatStoryResponse($bookmark->story)
    //         ];
    //     });

    //     // Set ulang koleksi paginasi dengan data yang sudah diformat
    //     $bookmarksPaginated->setCollection($formattedBookmarks);

    //     return $this->successResponse("Display Bookmark Successfully", $bookmarksPaginated);
    // }
    public function show(Request $request)
    {
        $pagination = $request->query('per_page', 10);
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 402,
                'success' => false,
                'message' => 'Unauthorized',
            ], 402);
        }

        $bookmarksPaginated = Bookmark::where('user_id', $user->id)
            ->with(['story.user', 'story.category'])->orderBy('created_at', 'desc')
            ->paginate($pagination);

        if ($bookmarksPaginated->isEmpty()) {
            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'No Bookmark Found for this user',
                'data' => []
            ], 404);
        }

        $formattedBookmarks = $bookmarksPaginated->getCollection()->map(function ($bookmark) {
            $story = $bookmark->story;

            if (!$story || !$story->user || !$story->category) {
                return null;
            }
            return [
                'id' => $bookmark->id,
                'user_id' => $bookmark->user_id,
                'username' => $bookmark->user->username,
                'story' => $this->formatStoryResponse($story)
            ];
        })->filter()->values();

        $bookmarksPaginated->setCollection($formattedBookmarks);

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Display Bookmark Successfully',
            'data' => $bookmarksPaginated
        ]);
    }

    //////////////////
    // public function show(Request $request)
    // {
    //     $pagination = $request->query('per_page', 100);
    //     $user = $request->user();
    //     if (!$user) {
    //         return response()->json([
    //             'status' => 402,
    //             'success' => false,
    //             'message' => 'Unauthorized',

    //         ], 402);
    //     }

    //     $bookmarkQuery = Bookmark::where('user_id', $user->id)
    //         ->with('story.user', 'story.category');

    //     $bookmarksPaginated = $bookmarkQuery->paginate($pagination);

    //     if ($bookmarksPaginated->isEmpty()) {
    //         return response()->json([
    //             'message' => 'No Bookmark Found for this user',
    //             'success' => false
    //         ], 404);
    //     }

    //     $formattedBookmarks = $bookmarksPaginated->getCollection()->map(function ($bookmark) {
    //         $story = $bookmark->story;

    //         if (!$story || !$story->user || !$story->category) {
    //             return null;
    //         }
    //         return [
    //             'id' => $bookmark->id,
    //             'user_id' => $bookmark->user_id,
    //             'username' => $bookmark->user->username,
    //             'story' => [
    //                 'id' => $story->id,
    //                 'title' => $story->title,
    //                 'cover' => $story->cover,
    //                 'created_at' => $story->created_at,
    //                 'content' => $story->content,
    //                 'category' => $story->category->name,
    //                 'author_id' => $story->user_id,
    //                 'author_name' => $story->user->username,
    //                 'author_image' => $story->user->image,

    //             ],
    //         ];
    //     })->filter()->values();
    //     $bookmarksPaginated->setCollection($formattedBookmarks);

    //     return response()->json([
    //         'status' => 200,
    //         "success" => true,
    //         'message' => 'Display Bookmark Successfully',
    //         'data' => $bookmarksPaginated
    //     ]);
    // }


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

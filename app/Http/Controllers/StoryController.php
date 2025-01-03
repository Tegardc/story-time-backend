<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Story;
use App\Models\StoryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $pagination = $request->query('page') ?? 10;
        $category = request()->query('category') ?? null;
        $story = $request->query('story') ?? null;
        $query = Story::query();
        if ($story) {
            $query->where('title', 'like', "%$story%");
        }
        if ($category) {
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('name', 'like', "%$category%");
            });
        }
        $query->with(['category']);
        $stories = $query->paginate($pagination);
        if ($stories->isEmpty() && $stories->currentPage() > $stories->lastPage()) {
            $stories = $query->paginate($pagination, ['*'], 'page', $stories->lastPage());
        }

        return response()->json(['message' => 'Successfully Display Data', 'success' => true, 'data' => $stories]);
    }
    // public function index()
    // {

    //     $story = Story::all();
    //     return  response()->json([
    //         'message' => 'Successfully Display Data',
    //         'success' => true,
    //         'data' => $story
    //     ]);
    // }

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
    // public function store(Request $request)
    // {
    //     try {
    //         $validateData = $request->validate([
    //             'title' => 'required|unique:stories,title',
    //             'content' => 'required|string|max:1000',
    //             'category_id' => 'required|exists:categories,id',
    //             'cover' => 'nullable|image|mimes:jpg,png,svg,gif,webp|max:2048',
    //         ]);

    //         $user = $request->user();
    //         $validateData['user_id'] = $user->id;

    //         $newStory = Story::create($validateData);
    //         return response()->json([
    //             'message' => "Added Story",
    //             'success' => true,
    //             'data' => $newStory
    //         ], 201);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'message' => $e->errors(),
    //             'success' => false
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error Created Data',
    //             'success' => false,
    //             'errors' => $e->getMessage()
    //         ], 500);
    //     }
    //     //
    // }
    public function store(Request $request)
    {
        try {
            $validateData = $request->validate([
                'title' => 'required|unique:stories,title',
                'content' => 'required|string|max:1000',
                'category_id' => 'required|exists:categories,id',
                'cover' => 'nullable|image|mimes:jpg,png,svg,gif,webp|max:2048',
                'images.*' => 'nullable|image|mimes:jpg,png,svg,gif,webp|max:2048', // Validasi untuk gambar tambahan
            ]);

            $user = $request->user();
            $validateData['user_id'] = $user->id;

            // Simpan cerita
            $newStory = Story::create($validateData);

            // Simpan gambar sampul jika ada
            if ($request->hasFile('cover')) {
                $coverPath = $request->file('cover')->store('story_covers', 'public');
                $newStory->cover = Storage::url($coverPath);
                $newStory->save();
            }

            // Simpan gambar tambahan jika ada
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePath = $image->store('story_images', 'public');
                    // Simpan ke tabel story_images
                    StoryImage::create([
                        'story_id' => $newStory->id,
                        'image_path' => Storage::url($imagePath),
                    ]);
                }
            }

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
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $story = Story::with('story_images')->find($id);

        if (!$story) {
            return response()->json([
                'message' => 'Story Not Found',
                'success' => false
            ], 404);
        }

        $data = [
            'story' => [
                'id' => $story->id,
                'title' => $story->title,
                'cover' => $story->cover,
                'created_story' => $story->created_at,
                'category' => $story->category->name,
                'images' => $story->story_images->pluck('image_path'),
                'content' => $story->content,


            ],
            'author' => [
                'author_id' => $story->user->id,
                'author_name' => $story->user->username,
                'image' => $story->user->image
            ]


        ];

        return response()->json([
            'message' => 'Successfully Display Data',
            'success' => true,
            'data' => $data
        ]);
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
            ], 401);
        }

        $story = Story::find($id);
        if (!$story) {
            return response()->json([
                'message' => 'Story not found',
                'success' => false,
            ], 404);
        }

        try {
            Log::info($request->all()); // Log data yang diterima

            $validateData = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string|max:1000',
                'category_id' => 'sometimes|exists:categories,id',
                'cover' => 'sometimes|image|mimes:jpg,png,svg,gif,webp|max:2048',
                'images.*' => 'sometimes|image|mimes:jpg,png,svg,gif,webp|max:2048'
            ]);

            $story->update($validateData);

            // Update cover jika ada
            if ($request->hasFile('cover')) {
                Log::info('Cover file received'); // Log jika file diterima
                if ($story->cover) {
                    $oldCoverPath = str_replace('/storage/', '', $story->cover);
                    Log::info('Deleting old cover: ' . $oldCoverPath); // Log path yang dihapus
                    Storage::delete($oldCoverPath); // Hapus file lama
                }
                $coverPath = $request->file('cover')->store('story_covers', 'public');
                $story->cover = Storage::url($coverPath); // Simpan URL ke database
                $story->save(); // Simpan perubahan
                Log::info('Updated story cover: ' . $story->cover); // Log cover yang diperbarui
            }

            // Simpan gambar tambahan jika ada
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePath = $image->store('story_images', 'public');
                    StoryImage::create([
                        'story_id' => $story->id,
                        'image_path' => Storage::url($imagePath),
                    ]);
                }
            }

            return response()->json([
                'message' => 'Updated Successfully',
                'success' => true,
                'data' => $story
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->errors(),
                'status' => false
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Updating Data: ' . $e->getMessage(),
                'status' => false
            ], 500);
        }
    }

    // public function update(Request $request, $id)
    // {
    //     $user = $request->user();
    //     if (!$user) {
    //         return response()->json([
    //             'message' => 'User Not Authenticated',
    //             'success' => false
    //         ], 404);
    //     }
    //     $story = Story::find($id);
    //     if (!$story) {
    //         return response()->json([
    //             'message' => 'Story not found',
    //             'success' => false,
    //         ], 404);
    //     }
    //     try {
    //         $validateData = $request->validate([
    //             'title' => 'required|unique:stories,title',
    //             'content' => 'required|max:1000',
    //             'category_id' => 'required|exists:categories,id',
    //             'cover' => 'required|image|mimes:jpg,png,svg,gif,webp|max:2018'
    //         ]);
    //         $story->update($validateData);
    //         return response()->json([
    //             'message' => 'Updated Success',
    //             'success' => true
    //         ], 200);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'message' => $e->errors(),
    //             'status' => false
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error Update Data',
    //             'status' => false
    //         ], 500);
    //         //throw $th;
    //     }
    //     //
    // }

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
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated',
                'success' => false,
            ], 401);
        }


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

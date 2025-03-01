<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoryRequest;
use App\Models\Category;
use App\Models\Story;
use App\Models\StoryImage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PDO;
use App\Traits\ApiResponseTrait;

class StoryController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    //Menampilkan Daftar Cerita
    public function index(Request $request)
    {
        try {
            // Ambil input dari request
            $category = $request->input('category') ?? null;
            $story = $request->input('title') ?? null;
            $sort = $request->input('sort') ?? null;
            $sortBy = $request->input('sort_by', 'created_at');
            $order = strtolower($request->input('order')) === 'asc' ? 'asc' : 'desc';
            $perPage = $request->input('per_page', 12);

            // Query awal dengan relasi kategori dan pengguna
            $query = Story::with(['category', 'user']);

            // Filter berdasarkan judul
            if ($story) {
                $query->where('title', 'like', "%$story%");
            }

            // Sorting berdasarkan pilihan pengguna
            if ($sort === 'a-z') {
                $sortBy = 'title';
                $order = 'asc';
            } elseif ($sort === 'z-a') {
                $sortBy = 'title';
                $order = 'desc';
            }

            // Validasi agar sort_by hanya bisa pakai kolom yang valid
            $validSortColumns = ['id', 'title', 'created_at'];
            if (!in_array($sortBy, $validSortColumns)) {
                $sortBy = 'created_at';
            }

            // Urutkan berdasarkan pilihan pengguna
            $query->orderBy($sortBy, $order);

            // Ambil data dengan pagination
            $stories = $query->paginate($perPage);

            // Cek jika tidak ada data
            if ($stories->isEmpty()) {
                return $this->errorResponse("No Stories Data", 404);
            }

            // Format data utama tanpa pagination
            $formattedData = collect($stories->items())->map(fn($story) => $this->formatStoryResponse($story));

            // Mengirimkan pagination di luar "data"
            return response()->json([

                'message' => "Successfully Displayed Data",
                'data' => $formattedData,
                'current_page' => $stories->currentPage(),
                'last_page' => $stories->lastPage(),
                'per_page' => $stories->perPage(),
                'total' => $stories->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse("Error Displayed Data: " . $e->getMessage(), 500);
        }
    }


    public function search(Request $request)
    {
        $query = Story::query();

        // Filter berdasarkan judul
        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->category . '%');
            });
        }

        $stories = $query->with(['category', 'user'])->get();
        if ($stories->isEmpty()) {
            return $this->errorResponse("Story Not Found", 404);
        }
        $formattedStories = $stories->map(fn($story) => $this->formatStoryResponse($story));

        return $this->successResponse("Successfully Displayed Story", $formattedStories);
    }


    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

    //Create Story//
    public function store(StoryRequest $request)
    {
        try {
            //Ambil pengguna yang sedang login
            $user = $request->user();

            // Validasi input
            $validated = $request->validated();
            $validated['user_id'] = $user->id;

            // Simpan cerita baru ke database
            $newStory = Story::create([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'category_id' => $validated['category_id'],
                'user_id' => $validated['user_id'],
                'cover' => $validated['cover'],
            ]);

            // Simpan gambar cerita
            foreach ($validated['images'] as $imageUrl) {
                StoryImage::create([
                    'story_id' => $newStory->id,
                    'image_path' => $imageUrl,
                ]);
            }

            // Load relasi setelah penyimpanan
            $newStory->load(['category', 'user', 'story_images']);
            return $this->successResponse("Story Added", $this->formatStoryResponse($newStory), 201);
        } catch (\Exception $e) {
            return $this->errorResponse("Error Creating Data: " . $e->getMessage(), 500);
        }
    }


    public function show($id)
    {
        $story = Story::with(
            'story_images',
            'category',
            'user'
        )->find($id);
        if (!$story) {
            return $this->errorResponse(
                "No Story Found",
                404
            );
        }
        return $this->successResponse(
            "Successfully Display Data",
            $this->formatStoryResponse($story)
        );
    }

    public function edit(Story $story)
    {
        //
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 401,
                'success' => false,
                'message' => 'User Not Authenticated',
            ], 401);
        }

        $story = Story::where('id', $id)->where('user_id', $user->id)->first();
        if (!$story) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'Story not found or you do not have permission to access it',
            ], 404);
        }
        try {
            $validateData = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'category_id' => 'sometimes|exists:categories,id',
                'cover' => 'sometimes|url',
                'images' => 'sometimes|array',
                'images.*' => 'url'
            ]);
            $story->update($validateData);
            if ($request->filled('cover')) {
                $story->cover = $request->cover;
                $story->save();
            }
            if ($request->filled('images')) {
                StoryImage::where('story_id', $story->id)->delete();
                foreach ($request->images as $imageUrl) {
                    StoryImage::create([
                        'story_id' => $story->id,
                        'image_path' => $imageUrl,
                    ]);
                }
            }

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Updated Successfully',
                'data' => $story->load('images')
            ], 200);
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
                'message' => 'Error Updating Data: ' . $e->getMessage(),
            ], 500);
        }
    }

    // public function update(Request $request, $id)
    // {
    //     $user = $request->user();
    //     if (!$user) {
    //         return $this->errorResponse("Unauthenticated", 401);
    //     }
    //     $story = Story::findOrFail($id);
    //     $this->authorize('update', $story);
    //     try {
    //         $validateData = $request->validate([
    //             'title' => 'sometimes|string|max:255',
    //             'content' => 'sometimes|string',
    //             'category_id' => 'sometimes|exists:categories,id',
    //             'cover' => 'sometimes|url',
    //             'images' => 'sometimes|array',
    //             'images.*' => 'url'
    //         ]);
    //         $story->update($validateData);
    //         if ($request->has('cover')) {
    //             $story->update(['cover' => $request->cover]);
    //         }
    //         if ($request->has('images')) {
    //             StoryImage::where('story_id', $story->id)->delete();
    //             $images = array_map(fn($url) => ['story_id' => $story->id, 'image_path' => $url], $request->images);
    //             StoryImage::insert($images);
    //         }
    //         return $this->successResponse("Updated Success", $this->formatStoryResponse($story));
    //     } catch (ValidationException $e) {
    //         return $this->errorResponse($e->errors(), 422);
    //     } catch (\Exception $e) {
    //         return $this->errorResponse("Error Updating Data: " . $e->getMessage(), 500);
    //     }
    // }

    public function destroy() {}
    public function getStoryUser(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 4);
        if (!$user) {
            return $this->errorResponse("Unauthenticated", 401);
        }
        $query = Story::where('user_id', $user->id)->with('category', 'user')->orderBy('created_at', 'desc');
        $stories = $query->paginate($perPage);

        return response()->json(
            [
                'message' => "Success Displayed Data",
                'data' => $stories->isEmpty() ? [] : $stories->map(fn($story) => $this->formatStoryResponse($story)),
                'current_page' => $stories->currentPage(),
                'last_page' => $stories->lastPage(),
                'per_page' => $stories->perPage(),
                'total' => $stories->total(),
            ]
        );
    }


    // public function popularStory(Request $request)
    // {
    //     try {
    //         $pagination = $request->query('per_page') ?? 12;
    //         $populerStories = Story::with(['user', 'category'])->withCount(['bookmarks' => function ($query) {
    //             $query->where('created_at', '>=', now()->subDays(100));
    //         }])->orderBy('bookmarks_count', 'desc')->paginate($pagination);

    //         if ($populerStories->isEmpty()) {
    //             return $this->errorResponse('No Story Found', 404);
    //         }
    //         $formattedData = $populerStories->getCollection()->map(fn($story) => $this->formatPopularResponse($story));
    //         $populerStories->setCollection($formattedData);

    //         return $this->successResponse("Success Displayed Popular Stories ", $populerStories);
    //     } catch (\Exception $e) {
    //         return $this->errorResponse("Error Displayed Data: " . $e->getMessage(), 500);
    //     }
    // }
    public function popularStory(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 12); // Default 12
            $populerStories = Story::with(['user', 'category'])
                ->withCount(['bookmarks' => function ($query) {
                    $query->where('created_at', '>=', now()->subDays(100));
                }])
                ->orderBy('bookmarks_count', 'desc')
                ->paginate($perPage);

            // Jika tidak ada data
            if ($populerStories->isEmpty()) {
                return $this->errorResponse("No Stories Data", 404);
            }

            // Format data agar tidak terjadi duplikasi dalam response
            $formattedData = collect($populerStories->items())->map(fn($story) => $this->formatPopularResponse($story));

            return response()->json([
                'message' => "Successfully Displayed Data",
                'data' => $formattedData,
                'current_page' => $populerStories->currentPage(),
                'last_page' => $populerStories->lastPage(),
                'per_page' => $populerStories->perPage(),
                'total' => $populerStories->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse("Error Displayed Data: " . $e->getMessage(), 500);
        }
    }

    public function newest(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 12);
            $query = Story::with(['category', 'user'])->orderBy('created_at', 'desc');
            $newestStories = $query->paginate($perPage);

            if ($newestStories->isEmpty()) {
                return $this->errorResponse(
                    "No Stories Data",
                    404
                );
            }
            return response()->json([
                'message' => "Successfully Displayed Data",
                'data' => collect($newestStories->items())->map(fn($story) => $this->formatStoryResponse($story)), // FIX DOUBLE DATA
                'current_page' => $newestStories->currentPage(),
                'last_page' => $newestStories->lastPage(),
                'per_page' => $newestStories->perPage(),
                'total' => $newestStories->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse("Error Displayed Data: " . $e->getMessage(), 500);
        }
    }
    public function similarStory($id)
    {
        try {
            $currentStory = Story::with('category')->find($id);

            if (!$currentStory) {
                return $this->errorResponse(
                    "Story Not Found",
                    404
                );
            }

            $similarStories = Story::with(['category', 'user'])->where('category_id', $currentStory->category_id)
                ->where('id', '!=', $currentStory->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            if ($similarStories->isEmpty()) {
                return $this->errorResponse(
                    "Similiar Story Not Found",
                    404
                );
            }
            return $this->successResponse("Successfully Displayed Data", $similarStories->map(fn($story) => $this->formatStoryResponse($story)));
        } catch (\Exception $e) {
            return $this->errorResponse("Error Displayed Data: " . $e->getMessage(), 500);
        }
    }
    public function deleteStory($id)
    {
        try {
            // Ambil pengguna yang sedang login
            $user = auth()->user();
            if (!$user) {
                return $this->errorResponse("Unauthenticated", 401);
            }
            // Cari cerita berdasarkan ID
            $story = Story::findOrFail($id);

            // Memastikan pengguna memiliki izin untuk menghapus cerita
            $this->authorize('delete', $story);

            // Hapus cerita
            $story->delete();
            return $this->successResponse("Delete Data Successfully", null, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse("Story Not Found", 404);
        } catch (AuthorizationException $e) {
            return $this->errorResponse("Sumimasen, Kore wa Kimi no janai desu", 403);
        } catch (\Exception $e) {
            return $this->errorResponse("Error Updating Data: " . $e->getMessage(), 500);
        }
    }
    public function restore($id)
    {
        try {
            $user = auth()->user(); // Mendapatkan pengguna yang sedang login
            $story = Story::onlyTrashed()->where('id', $id)->where('user_id', $user->id)->first();

            if (!$story) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'Story not found in trash or you do not have permission to restore this story',
                ], 404);
            }

            // Pulihkan data
            $story->restore();

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Story successfully restored',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error restoring story:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }
    public function trashedStories()
    {
        try {
            $user = auth()->user(); // Mendapatkan pengguna yang sedang login
            $stories = Story::onlyTrashed()->where('user_id', $user->id)->get();

            if ($stories->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'No trashed stories found',
                    'data' => [],
                ], 404);
            }

            $data = $stories->map(function ($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'deleted_at' => $story->deleted_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Trashed stories retrieved successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching trashed stories:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An unexpected error occurred',
                'data' => [],
            ], 500);
        }
    }
}

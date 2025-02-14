<?php

namespace App\Http\Controllers;

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

class StoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $category = $request->query('category') ?? null;
            $story = $request->query('title') ?? null;
            $sort = $request->query('sort') ?? null;
            $sortBy = $request->query('sort_by', 'created_at');
            $order = strtolower($request->query('order')) === 'asc' ? 'asc' : 'desc';

            $query = Story::query()->with(['category', 'user']);

            if ($story) {
                $query->where('title', 'like', "%$story%");
            }

            if ($category) {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('name', 'like', "%$category%");
                });
            }
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

            $query->orderBy($sortBy, $order);

            // Ambil semua data tanpa pagination
            $stories = $query->get();

            if ($stories->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'No stories found',
                    'data' => [],
                ], 404);
            }

            // Format hasil data agar sama dengan getStoryUser()
            $formattedStories = $stories->map(function ($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'cover' => $story->cover,
                    'content' => $story->content,
                    'created_at' => $story->created_at->format('Y-m-d H:i:s'),
                    'category' => $story->category->name ?? null,
                    'author_id' => $story->user_id,
                    'author_name' => $story->user->username ?? null,
                    'author_image' => $story->user->image ?? null,
                ];
            });

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Successfully Displayed Stories',
                'data' => $formattedStories,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Error retrieving stories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function search(Request $request)
    {
        $query = Story::query();

        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->category . '%');
            });
        }

        $stories = $query->with(['category', 'user'])->get();

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Search results',
            'data' => $stories
        ], 200);
    }




    // public function index(Request $request)
    // {
    //     $pagination = $request->query('page') ?? 10;
    //     $category = $request->query('category') ?? null;
    //     $story = $request->query('story') ?? null;
    //     $sortBy = $request->query('sort_by') ?? 'created_at';
    //     $order = strtolower($request->query('order')) === 'asc' ? 'asc' : 'desc';
    //     $query = Story::query();

    //     if ($story) {
    //         $query->where('title', 'like', "%$story%");
    //     }

    //     if ($category) {
    //         $query->whereHas('category', function ($q) use ($category) {
    //             $q->where('name', 'like', "%$category%");
    //         });
    //     }

    //     $query->with(['category']);

    //     // Tambahkan sorting berdasarkan parameter yang dikirim
    //     $query->orderBy($sortBy, $order);

    //     $stories = $query->paginate($pagination);

    //     if ($stories->isEmpty() && $stories->currentPage() > $stories->lastPage()) {
    //         $stories = $query->paginate($pagination, ['*'], 'page', $stories->lastPage());
    //     }

    //     return response()->json([
    //         'status' => 200,
    //         'success' => true,
    //         'message' => 'Successfully Display Data',
    //         'data' => $stories
    //     ], 200);
    // }
    //     $pagination = $request->query('page') ?? 10;
    //     $category = request()->query('category') ?? null;
    //     $story = $request->query('story') ?? null;
    //     $query = Story::query();
    //     if ($story) {
    //         $query->where('title', 'like', "%$story%");
    //     }
    //     if ($category) {
    //         $query->whereHas('category', function ($q) use ($category) {
    //             $q->where('name', 'like', "%$category%");
    //         });
    //     }
    //     $query->with(['category']);
    //     $stories = $query->paginate($pagination);
    //     if ($stories->isEmpty() && $stories->currentPage() > $stories->lastPage()) {
    //         $stories = $query->paginate($pagination, ['*'], 'page', $stories->lastPage());
    //     }
    //     return response()->json([
    //         'status' => 200,
    //         'success' => true,
    //         'message' => 'Successfully Display Data',
    //         'data' => $stories
    //     ], 200);
    // }



    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

    //Create Story dengan Raw//
    public function store(Request $request)
    {
        try {
            $validateData = $request->validate([
                'title' => 'required|unique:stories,title',
                'content' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'cover' => 'required|url',
                'images' => 'required|array',
                'images.*' => 'url'
            ]);

            $user = $request->user();
            $validateData['user_id'] = $user->id;
            $newStory = Story::create([
                'title' => $validateData['title'],
                'content' => $validateData['content'],
                'category_id' => $validateData['category_id'],
                'user_id' => $validateData['user_id'],
                'cover' => $validateData['cover'],
            ]);

            foreach ($validateData['images'] as $imageUrl) {
                StoryImage::create([
                    'story_id' => $newStory->id,
                    'image_path' => $imageUrl,
                ]);
            }
            return response()->json([
                'status' => 201,
                'success' => true,
                'message' => "Story Added Successfully",
                'data' => $newStory->load('images')
            ], 201);
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
                'message' => 'Error Creating Data',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    //Create dengan Form Data///
    public function createStory(Request $request)
    {
        try {
            $validateData = $request->validate([
                'title' => 'required|unique:stories,title',
                'content' => 'required|string|max:1000',
                'category_id' => 'required|exists:categories,id',
                'cover' => 'nullable|image|mimes:jpg,png,svg,gif,webp|max:2048',
                'images.*' => 'nullable|image|mimes:jpg,png,svg,gif,webp|max:2048',
            ]);

            $user = $request->user();
            $validateData['user_id'] = $user->id;

            $newStory = Story::create($validateData);

            if ($request->hasFile('cover')) {
                $coverPath = $request->file('cover')->store('story_covers', 'public');
                $newStory->cover = Storage::url($coverPath);
                $newStory->save();
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePath = $image->store('story_images', 'public');
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
        $story = Story::with('story_images', 'category', 'user')->find($id);
        if (!$story) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'Story Not Found',
            ], 404);
        }

        $data = [
            'id' => $story->id,
            'title' => $story->title,
            'cover' => $story->cover,
            'created_at' => $story->created_at,
            'category' => $story->category->name,
            'images' => $story->story_images->pluck('image_path'),
            'content' => $story->content,
            'author_id' => $story->user->id,
            'author_name' => $story->user->username,
            'image' => $story->user->image

        ];

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Successfully Display Data',
            'data' => $data
        ], 200);
    }
    // public function getStoryByCategory($id) {
    //     $story = Story::with('categories')->find($id);
    //     if(!$story){
    //         return response()->json(['message'=>'Story Not Found','success'=>false],404);
    //     }
    //     $data = ['category'=>['id'=>$story->category->id,'name'=>$story->category->name]]
    // }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Story $story)
    {
        //
    }

    /**
     * 
     * Update the specified resource in storage.
     */

    //UPDATE YANG DI COBA MENGGUNAkAN RAW//////
    public function update(Request $request, $id)
    {
        $user = $request->user();
        abort_if(!$user, 401, 'User Not Aunthenticated');
        // if (!$user) {
        //     return response()->json([
        //         'status' => 401,
        //         'success' => false,
        //         'message' => 'User Not Authenticated',
        //     ], 401);
        // }
        $story = Story::findOrFail($id);
        $this->authorize('update', $story);
        // $story = Story::where('id', $id)->where('user_id', $user->id)->first();
        // abort_if(!$user)
        // if (!$story) {
        //     return response()->json([
        //         'status' => 404,
        //         'success' => false,
        //         'message' => 'Story not found or you do not have permission to access it',
        //     ], 404);
        // }
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
            if ($request->has('cover')) {
                $story->update(['cover' => $request->cover]);
            }
            if ($request->has('images')) {
                StoryImage::where('story_id', $story->id)->delete();

                $images = array_map(fn($url) => ['story_id' => $story->id, 'image_path' => $url], $request->images);
                StoryImage::insert($images);
            }
            // $story->update($validateData);
            // if ($request->filled('cover')) {
            //     $story->cover = $request->cover;
            //     $story->save();
            // }
            // if ($request->filled('images')) {
            //     StoryImage::where('story_id', $story->id)->delete();

            //     foreach ($request->images as $imageUrl) {
            //         StoryImage::create([
            //             'story_id' => $story->id,
            //             'image_path' => $imageUrl,
            //         ]);
            //     }
            // }

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
    //         return response()->json([
    //             'status' => 401,
    //             'success' => false,
    //             'message' => 'User Not Authenticated',
    //         ], 401);
    //     }

    //     $story = Story::where('id', $id)->where('user_id', $user->id)->first();
    //     if (!$story) {
    //         return response()->json([
    //             'status' => 404,
    //             'success' => false,
    //             'message' => 'Story not found or you do not have permission to access it',
    //         ], 404);
    //     }
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
    //         if ($request->filled('cover')) {
    //             $story->cover = $request->cover;
    //             $story->save();
    //         }
    //         if ($request->filled('images')) {
    //             StoryImage::where('story_id', $story->id)->delete();

    //             foreach ($request->images as $imageUrl) {
    //                 StoryImage::create([
    //                     'story_id' => $story->id,
    //                     'image_path' => $imageUrl,
    //                 ]);
    //             }
    //         }

    //         return response()->json([
    //             'status' => 200,
    //             'success' => true,
    //             'message' => 'Updated Successfully',
    //             'data' => $story->load('images')
    //         ], 200);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'status' => 422,
    //             'success' => false,
    //             'message' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 500,
    //             'success' => false,
    //             'message' => 'Error Updating Data: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    //UPDATE DENGAN FORMDATA////
    // public function updateStory(Request $request, $id)
    // {
    //     $user = $request->user();
    //     if (!$user) {
    //         return response()->json([
    //             'message' => 'User Not Authenticated',
    //             'success' => false
    //         ], 401);
    //     }

    //     $story = Story::where('id', $id)->where('user_id', $user->id)->first();
    //     if (!$story) {
    //         return response()->json([
    //             'message' => 'Story not found or you do not have permission to access it',
    //             'success' => false,
    //         ], 404);
    //     }

    //     try {
    //         Log::info($request->all());
    //         $validateData = $request->validate([
    //             'title' => 'sometimes|string|max:255',
    //             'content' => 'sometimes|string|max:1000',
    //             'category_id' => 'sometimes|exists:categories,id',
    //             'cover' => 'sometimes|image|mimes:jpg,png,svg,gif,webp|max:2048',
    //             'images.*' => 'sometimes|image|mimes:jpg,png,svg,gif,webp|max:2048'
    //         ]);

    //         $story->update($validateData);
    //         if ($request->hasFile('cover')) {
    //             Log::info('Cover file received');
    //             if ($story->cover) {
    //                 $oldCoverPath = str_replace('/storage/', '', $story->cover);
    //                 Log::info('Deleting old cover: ' . $oldCoverPath);
    //                 Storage::delete($oldCoverPath);
    //             }
    //             $coverPath = $request->file('cover')->store('story_covers', 'public');
    //             $story->cover = Storage::url($coverPath);
    //             $story->save();
    //             Log::info('Updated story cover: ' . $story->cover);
    //         }
    //         if ($request->hasFile('images')) {
    //             $oldImages = StoryImage::where('story_id', $story->id)->get();
    //             foreach ($oldImages as $oldImage) {
    //                 $oldImagePath = str_replace('/storage/', '', $oldImage->image_path);
    //                 Log::info('Deleting old image: ' . $oldImagePath);
    //                 Storage::delete($oldImagePath);
    //                 $oldImage->delete();
    //             }
    //             foreach ($request->file('images') as $image) {
    //                 $imagePath = $image->store('story_images', 'public');
    //                 StoryImage::create([
    //                     'story_id' => $story->id,
    //                     'image_path' => Storage::url($imagePath),
    //                 ]);
    //             }
    //         }

    //         return response()->json([
    //             'message' => 'Updated Successfully',
    //             'success' => true,
    //             'data' => $story
    //         ], 200);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'message' => $e->errors(),
    //             'status' => false
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error Updating Data: ' . $e->getMessage(),
    //             'status' => false
    //         ], 500);
    //     }
    // }

    public function destroy(Request $request, $id)
    {
        $story = Story::find($id);
        if (!$story) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'Story Not Found',
            ], 404);
        }
        $isYourStory = $request->user()->id;
        if ($story->user_id !== $isYourStory) {
            return response([
                'status' => 403,
                'success' => false,
                'message' => 'これはきみのしょうせつじゃないです'
            ], 403);
        }
        $story->delete();
        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'deleted Success',

        ], 200);
        //
    }
    public function getStoryUser(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $stories = Story::where('user_id', $user->id)->with('category', 'user')->orderBy('created_at', 'desc')->get();

        if ($stories->isEmpty()) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'No stories found for this user',
                'data' => [],
            ], 404);
        }

        $formattedStories = $stories->map(function ($story) {
            return [
                'id' => $story->id,
                'title' => $story->title,
                'cover' => $story->cover,
                'content' => $story->content,
                'created_at' => $story->created_at->format('Y-m-d H:i:s'),
                'category' => $story->category->name ?? null,
                'author_id' => $story->user_id,
                'author_name' => $story->user->username ?? null,
                'author_image' => $story->user->image ?? null,
            ];
        });

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Successfully Displayed Stories',
            'data' => $formattedStories,
        ], 200);
    }

    // public function getStoryUser(Request $request)
    // {
    //     $pagination = $request->query('per_page', 5);

    //     $user = $request->user();

    //     if (!$user) {
    //         return response()->json([
    //             'status' => 401,
    //             'success' => false,
    //             'message' => 'User not authenticated',
    //         ], 401);
    //     }
    //     $storiesQuery = Story::where('user_id', $user->id);

    //     $storiesPaginated = $storiesQuery->paginate($pagination);
    //     if ($storiesPaginated->isEmpty()) {
    //         return response()->json([
    //             'status' => 404,
    //             'success' => false,
    //             'message' => 'No stories found for this user',
    //         ], 404);
    //     }
    //     $formattedStories = $storiesPaginated->getCollection()->map(function ($stories) {
    //         $story = $stories;
    //         return [
    //             'id' => $story->id,
    //             'title' => $story->title,
    //             'cover' => $story->cover,
    //             'created_at' => $story->created_at,
    //             'category' => $story->category->name,
    //             'author_id' => $story->user_id,
    //             'author_name' => $story->user->username,
    //             'author_image' => $story->user->image,


    //         ];
    //     })->filter()->values();
    //     $storiesPaginated->setCollection($formattedStories);


    //     return response()->json([
    //         'status' => 200,
    //         'success' => true,
    //         'message' => 'Successfully Displayed Stories',
    //         'data' => $storiesPaginated,
    //     ], 200);
    // }

    public function popularStory(Request $request)
    {
        try {
            $pagination = $request->query('per_page') ?? 10;
            $populerStories = Story::with(['user', 'category'])->withCount(['bookmarks' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(100));
            }])->orderBy('bookmarks_count', 'desc')->paginate($pagination);

            if ($populerStories->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'No Populer Stories Available',
                    'data' => []
                ], 404);
            }
            $data = $populerStories->getCollection()->map(function ($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'cover' => $story->cover,
                    'bookmark_count' => $story->bookmarks_count,
                    'created_at' => $story->created_at->format('Y-m-d H:i:s'),
                    'category' => $story->category ? $story->category->name : null,
                    'author_id' => $story->user ? $story->user->id : null,
                    'author_name' => $story->user ? $story->user->name : null,
                    'author_image' => $story->user ? $story->user->image : null

                ];
            });
            $populerStories->setCollection($data);
            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Populer Stories retrived successfully',
                'data' => $populerStories
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching popular stories:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An unexpected error occurred',
                'data' => [],
            ], 500);
        }
    }
    public function newest()
    {
        try {
            $newestStories = Story::with(['category', 'user'])->orderBy('created_at', 'desc')
                // ->take(10)
                ->get();

            if ($newestStories->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'No New Stories Available',
                    'data' => []
                ], 404);
            }
            $data = $newestStories->map(function ($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'cover' => $story->cover,
                    'updated_at' => $story->updated_at->format('Y-m-d H:i:s'),
                    'created_at' => $story->created_at->format('Y-m-d H:i:s'),
                    'category' => $story->category->name,
                    'author_id' => $story->user->id,
                    'author_name' => $story->user->name,
                    'author_image' => $story->user->image
                ];
            });

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Newest Stories retrieved successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching newest stories:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An unexpected error occurred',
                'data' => [],
            ], 500);
        }
    }
    public function similarStory($id)
    {
        try {
            $currentStory = Story::with('category')->find($id);

            if (!$currentStory) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'Story Not Found',
                ], 404);
            }

            $similarStories = Story::with(['category', 'user'])->where('category_id', $currentStory->category_id)
                ->where('id', '!=', $currentStory->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            if ($similarStories->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'Similiar Story is Not Found',
                    'data' => []
                ], 404);
            }
            $data = $similarStories->map(function ($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'cover' => $story->cover,
                    'updated_at' => $story->updated_at->format('Y-m-d H:i:s'),
                    'created_at' => $story->created_at->format('Y-m-d H:i:s'),
                    'category' => $story->category->name,
                    'author_id' => $story->user->id,
                    'author_name' => $story->user->name,
                    'author_image' => $story->user->image
                ];
            });
            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Success Showing Similiar Story ',
                'data' => $data

            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching newest stories:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An unexpected error occurred',
                'data' => [],
            ], 500);
        }
    }
    public function deleteStory($id)
    {
        try {
            $user = auth()->user();
            // $story = Story::where('id', $id)->where('user_id', $user->id)->first();
            if (!$user) {
                return response()->json([
                    'status' => 401,
                    'success' => false,
                    'message' => 'User Not Authenticated',
                ], 401);
            }
            $story = Story::findOrFail($id);
            $this->authorize('delete', $story);
            // if (!$story) {
            //     return response()->json([
            //         'status' => 404,
            //         'success' => false,
            //         'message' => 'Story not found or you do not have permission to delete this story',
            //     ], 404);
            // }

            // Lakukan soft delete
            $story->delete();
            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Story successfully moved to trash',
            ], 200);
        } catch (AuthorizationException $e) { // Tangani error ketika user tidak punya izin
            return response()->json([
                'status' => 403, // 403 Forbidden
                'success' => false,
                'message' => 'You do not have permission to delete this story',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error deleting story:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], 500);
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

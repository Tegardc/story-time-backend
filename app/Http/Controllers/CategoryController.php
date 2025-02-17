<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Traits\ApiResponseTrait;

class CategoryController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $category = Category::all();
        return $this->successResponse("Success Display Data", $category, 200);

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
            $validateData = $request->validate(['name' => 'required|unique:categories,name'], ['name.required' => 'Category Name is Already']);
            $newData = Category::create($validateData);
            return response()->json([
                'message' => 'Added Category',
                'success' => true
            ], 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse("Error Creating Data: " . $e->getMessage(), 500);
        }
        //
    }
    // public function showStoryByCategory(Request $request)
    // {
    //     try {
    //         $categories = Category::with(['stories' => function ($query) {
    //             $query->take(3)->with('user');
    //         }])->get();

    //         if ($categories->isEmpty()) {
    //             return $this->errorResponse("Category Not Found", 404);
    //         }
    //         $formattedCategories = $categories->map(function ($category) {
    //             return [
    //                 'id' => $category->id,
    //                 'category_name' => $category->name,
    //                 'stories' => $category->stories->map(fn($story) => $this->formatStoryResponse($story))
    //             ];
    //         });
    //         return $this->successResponse("Successfully Display Data", $formattedCategories);
    //     } catch (\Exception $e) {
    //         return $this->errorResponse("Error Displayed Data: " . $e->getMessage(), 500);
    //     }
    // }
    public function showStoryByCategory(Request $request)
    {
        try {
            $categories = Category::with(['stories.user' => function ($query) {
                $query->take(3);
            }])->get();

            if ($categories->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'No categories found',
                ], 404);
            }
            $data = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'category_name' => $category->name,
                    'stories' => $category->stories->take(3)->map(function ($story) {
                        return [
                            'id' => $story->id,
                            'title' => $story->title,
                            'content' => $story->content,
                            'cover' => $story->cover,
                            'created_at' => $story->created_at,
                            'author_id' => optional($story->user)->id, // Cegah error jika user null
                            'author_name' => optional($story->user)->username, // Gunakan optional()
                            'author_image' => optional($story->user)->image,
                        ];
                    }),
                ];
            });
            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Categories and stories retrieved successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Error retrieving categories and stories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function show(Request $request, $id)
    {
        try {
            $pagination = $request->query('per_page', 5);

            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'Category not found',
                ], 404);
            }

            $storiesQuery = $category->stories()->with('user');
            $storiesPaginated = $storiesQuery->paginate($pagination);

            $formattedStories = $storiesPaginated->getCollection()->map(function ($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'cover' => $story->cover,
                    'created_at' => $story->created_at,
                    'author_id' => $story->user->id,
                    'author_name' => $story->user->username,
                    'author_image' => $story->user->image,
                ];
            });

            $storiesPaginated->setCollection($formattedStories);

            $data = [
                'category' => [
                    'id' => $category->id,
                    'category_name' => $category->name,
                ],
                'stories' => $storiesPaginated,
            ];

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Category found',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Error retrieving category data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

// public function getStoryByCategory($id)
// {
//     $category = Category::with('stories')->find($id);
//     if (!$category) {
//         return $this->errorResponse("Story Not Found", 404);
//     }
//     return response()->json([
//         'message' => 'Category found',
//         'success' => true,
//         'data' => $category
//     ], 200);
// }

/**
 * Display the specified resource.
 */
    // public function show(Request $request, $id)
    // {
    //     try {
    //         $pagination = $request->query('per_page', 5);

    //         $category = Category::find($id);

    //         if (!$category) {
    //             return $this->errorResponse("Category Not Found", 404);
    //         }
    //         $storiesQuery = $category->stories()->with('user');
    //         $storiesPaginated = $storiesQuery->paginate($pagination);
    //         return $this->successResponse("Successfully Displayed Data", $category->map(fn($story) => $this->formatStoryResponse($story)));
    //         // $formattedStories = $storiesPaginated->getCollection()->map(function ($story) {
    //         //     return [
    //         //         'id' => $story->id,
    //         //         'title' => $story->title,
    //         //         'content' => $story->content,
    //         //         'cover' => $story->cover,
    //         //         'created_at' => $story->created_at,
    //         //         'author_id' => $story->user->id,
    //         //         'author_name' => $story->user->username,
    //         //         'author_image' => $story->user->image,
    //         //     ];
    //         // });

    //         // $storiesPaginated->setCollection($formattedStories);

    //         // $data = [
    //         //     'category' => [
    //         //         'id' => $category->id,
    //         //         'category_name' => $category->name,
    //         //     ],
    //         //     'stories' => $storiesPaginated,
    //         // ];

    //         // return response()->json([
    //         //     'status' => 200,
    //         //     'success' => true,
    //         //     'message' => 'Category found',
    //         //     'data' => $data,
    //         // ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 500,
    //             'success' => false,
    //             'message' => 'Error retrieving category data',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
  

//     public function showStory($id)
//     {
//         $category = Category::with('stories')->find($id);

//         // Jika kategori tidak ditemukan
//         if (!$category) {
//             return response()->json([
//                 'message' => 'Category not found',
//                 'success' => false
//             ], 404);
//         }
//         return response()->json([
//             'message' => 'Category found',
//             'success' => true,
//             'data' => $category
//         ], 200);
//     }
//     /**
//      * Show the form for editing the specified resource.
//      */
//     public function edit(Category $category)
//     {
//         //
//     }

//     /**
//      * Update the specified resource in storage.
//      */
//     public function update(Request $request, Category $category)
//     {
//         //
//     }

//     /**
//      * Remove the specified resource from storage.
//      */
//     public function destroy(Category $category)
//     {
//         //
//     }
// }

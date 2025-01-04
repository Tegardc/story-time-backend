<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $category = Category::all();
        return response()->json(['message' => 'Successfully Display Data', 'success' => true, 'data' => $category]);
        //
    }
    public function getStoryByCategory($id)
    {
        // Cari kategori berdasarkan ID
        $category = Category::with('stories')->find($id);

        // Jika kategori tidak ditemukan
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
                'success' => false
            ], 404);
        }
        return response()->json([
            'message' => 'Category found',
            'success' => true,
            'data' => $category
        ], 200);
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
            return response()->json([
                'message' => $e->errors(),
                'success' => false,
            ], 422);
            //throw $th;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Create Data',
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
        $category = Category::with('stories')->find($id);

        // Jika kategori tidak ditemukan
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
                'success' => false
            ], 404);
        }
        return response()->json([
            'message' => 'Category found',
            'success' => true,
            'data' => $category
        ], 200);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        //
    }
}

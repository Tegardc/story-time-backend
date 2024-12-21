<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
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
            return response()->json(['message' => 'Added Bookmark', "success" => true, 'data' => $newBookmark]);
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
        $bookmark = Bookmark::where('user_id', $user->id)->get();
        if ($bookmark->isEmpty()) {
            return response()->json(['message' => 'No Bookmark Found for this user', 'success' => false], 404);
        }
        return response()->json(['message' => 'Successfully Display Bookmark', 'success' => true, 'data' => $bookmark], 200);

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

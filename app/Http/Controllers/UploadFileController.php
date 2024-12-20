<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UploadFileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }
    public function uploadFile(Request $request)
    {
        try {
            $request->validate(['file' => ['required', 'file']]);
            $file = $request->file('file');
            if (!$file->isValid()) {
                return response()->json([
                    'message' => 'File invalid',
                    'success' => false,
                    'data' => null
                ], 422);
            }

            $fileName = time();
            $resultFile = $file->storeAs('photos', "{$fileName}.{$file->extension()}");

            $baseUrl = Storage::url($resultFile);

            return response()->json([
                'message' => 'Upload File Success',
                'success' => true,
                'data' => ['url' => $baseUrl]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->errors(),
                'success' => false,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something Wrong, Please Try Again',
                'success' => false
            ], 500);
        }
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
    // public function uploadFile(Request $request)
    // {
    //     try {
    //         $request->validate(['file' => ['required', 'file']]);
    //         $file = $request->file('file');
    //         if (!$file->isValid()) {
    //             return response()->json([
    //                 'message' => 'File invalid',
    //                 'success' => false,
    //                 'data' => null
    //             ], 422);
    //         }

    //         $fileName = time();
    //         $resultFile = $file->storeAs('photos', "{$fileName}.{$file->extension()}");

    //         $baseUrl = Storage::url($resultFile);

    //         return response()->json([
    //             'message' => 'Upload File Success',
    //             'success' => true,
    //             'data' => ['url' => $baseUrl]
    //         ], 200);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'message' => $e->errors(),
    //             'success' => false,
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Something Wrong, Please Try Again',
    //             'success' => false
    //         ], 500);
    //     }
    // }

    /**
     * Show the form for creating a new resource.
     */
    // // 

    public function uploadFile(Request $request)
    {
        try {
            $request->validate(['files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048']]);
            $uploadedFiles = [];
            foreach ($request->file('files') as $file) {
                if (!$file->isValid()) {
                    return response()->json([
                        'message' => 'File tidak valid',
                        'status' => 'error',
                        'data' => null
                    ], 422);
                }
                $fileName = time() . '_' . uniqid();
                $resultFile = $file->storeAs('photos', "{$fileName}.{$file->extension()}", 'public');
                $baseUrl = Storage::url($resultFile);
                $uploadedFiles[] = $baseUrl;
            }
            return response()->json([
                'message' => 'Upload Files Success',
                'status' => true,
                'data' => ['urls' => $uploadedFiles]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
                'status' => false
            ], 500);
        }
    }
    public function upload(Request $request)
    {

        try {
            // Validasi file
            $request->validate([
                'files' => ['required'],
                'files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048']
            ]);

            $uploadedFiles = [];

            // Cek apakah hanya 1 file yang diunggah
            if ($request->hasFile('files') && is_array($request->file('files'))) {
                foreach ($request->file('files') as $file) {
                    if (!$file->isValid()) {
                        return response()->json([
                            'message' => 'File tidak valid',
                            'status' => 'error',
                            'data' => null
                        ], 422);
                    }

                    // Simpan file dengan nama unik
                    $fileName = time() . '_' . uniqid();
                    $resultFile = $file->storeAs('photos', "{$fileName}.{$file->extension()}", 'public');
                    $baseUrl = Storage::url($resultFile);

                    // Tambahkan URL ke dalam array hasil
                    $uploadedFiles[] = $baseUrl;
                }
            }

            // Jika hanya 1 file, kembalikan sebagai string
            if (count($uploadedFiles) === 1) {
                return response()->json([
                    'message' => 'Upload File Success',
                    'status' => true,
                    'data' => $uploadedFiles[0]
                ], 200);
            }

            // Jika lebih dari 1 file, kembalikan sebagai array
            return response()->json([
                'message' => 'Upload Files Success',
                'status' => true,
                'data' => $uploadedFiles
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
                'status' => false
            ], 500);
        }
    }
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

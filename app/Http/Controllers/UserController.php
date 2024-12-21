<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

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
    public function show(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $filteredUser = $user->only([
                'name',
                'username',
                'email',
                'image',
                'aboutme'
            ]);
            return response()->json([
                'message' => 'Successfully Display Data',
                'success' => true,
                'data' => $filteredUser
            ]);
        } else {
            return response()->json([
                'Message' => 'User Not Found',
                'success' => false
            ], 404);
        }
        // public function show($id)
        // {
        //     $user = User::find($id);
        //     if ($user) {
        //         $filteredUser = $user->only(['name', 'username', 'email', 'image', 'aboutme']);
        //         return response()->json([
        //             'message' => 'Successfully Display Data',
        //             'success' => true,
        //             'data' => $filteredUser
        //         ]);
        //     } else {
        //         return response()->json([
        //             'Message' => 'User Not Found',
        //             'success' => false
        //         ], 404);
        //     }
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
    // public function update(Request $request)
    // {
    //     // $user = User::find($id);
    //     // if (!$user) {
    //     //     return response()->json(['message' => 'Users Not Found', 'status' => false], 404);
    //     // }

    //     try {
    //         $user = $request->user();
    //         $validateData = $request->validate([
    //             'name' => 'required',
    //             'image' => 'nullable',
    //             'aboutme' => 'nullable|string|max:1000',

    //         ]);
    //         $user->update($validateData);
    //         return response()->json([
    //             'message' => 'User Updated Successfully',
    //             'success' => true
    //         ], 200);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'message' => $e->errors(),
    //             'success' => false
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error Update Data',
    //             'success' => false
    //         ], 500);
    //     }
    //     //
    // }
    public function update(Request $request)
    {
        // $user = User::find($id);
        // if (!$user) {
        //     return response()->json(['message' => 'Users Not Found', 'status' => false], 404);
        // }

        try {
            $user = $request->user();
            $validatedData = $request->validate([
                'name' => 'required',
                'image' => 'nullable',
                'aboutme' => 'nullable|string|max:1000',
                'current_password',
                'new_password' => 'min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$!%*?&])[A-Za-z\d@#$!%*?&]+$/',
            ]);
            if (!empty($validatedData['current_password']) && !empty($validatedData['new_password'])) {
                if (!Hash::check($validatedData['current_password'], $user->password)) {
                    return response()->json([
                        'message' => 'Current password is incorrect',
                        'success' => false,
                    ], 400);
                }
                $user->password = Hash::make($validatedData['new_password']);
            }

            // Update data lainnya (kecuali password)
            $user->fill($validatedData);
            $user->save();

            return response()->json([
                'message' => 'User Updated Successfully',
                'success' => true
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->errors(),
                'success' => false
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Update Data',
                'success' => false
            ], 500);
        };
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    //=================REGISTER===================
    public function regis(Request $request)
    {
        try {
            $validateData = $request->validate([
                'name' => 'required',
                'username' => 'required|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$!%*?&])[A-Za-z\d@#$!%*?&]+$/'
                ],
                'confirm_password' => 'required|string||same:password',
                'image' => 'nullable'
            ]);
            $validateData['password'] = Hash::make($validateData['password']);

            $newUser = User::create($validateData);
            $plainTextToken = $newUser->createToken('API Token')->plainTextToken;
            return response()->json([
                'message' => 'Register Successfully',
                'success' => true,
                'data' => [
                    'user' => $newUser,
                    'token' => $plainTextToken
                ]

            ], 201);
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

    //=================LOGIN==========================
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);
        $loginField = str_contains($credentials['email'], '@') ? 'email' : 'username';

        if (!Auth::attempt([$loginField => $credentials['email'], 'password' => $credentials['password']])) {
            return response()->json([
                'message' => 'Invalid credentials or account disabled',
                'success' => false,
                'data' => null,
            ], 400);
        }

        $user = Auth::user();

        $user->tokens()->delete();
        $plainTextToken = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'message' => "Login Successfully",
            'success' => true,
            'data' => [
                'token' => $plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'image' => $user->image,
                ]
            ]
        ], 200);
    }

    // public function login(Request $request)
    // {
    //     try {
    //         $credentials = $request->validate([
    //             'email' => 'required',
    //             'password' => 'required',
    //         ]);
    //         $loginField = str_contains($credentials['email'], '@') ? 'email' : 'username';
    //         $loginCredentials = [
    //             $loginField => $credentials['email'],
    //             'password' => $credentials['password']
    //         ];
    //         if (!Auth::attempt($loginCredentials)) {
    //             return response()->json([
    //                 'message' => 'Invalid credentials or account disabled',
    //                 'success' => false,
    //                 'data' => null,
    //             ], 400);
    //         }
    //         $user = Auth::user();
    //         $user = User::where('email', $credentials['email'])
    //             ->orWhere('username', $credentials['email'])
    //             ->first();
    //         if (!$user) {
    //             return response()->json([
    //                 'message' => 'User not found. Please register first.',
    //                 'success' => false,
    //                 'data' => null,
    //             ], 404);
    //         }
    //         $user->tokens()->delete();
    //         $hours = (int) 4;
    //         $plainTextToken = $user->createToken($user->email, ['*'], now()->addHours($hours))->plainTextToken;
    //         // $generateToken = $user->createToken($user->email, ['*'], now()->addHours($hours));
    //         return response()->json([
    //             'message' => "Login Successfully",
    //             'success' => true,
    //             "data" => ["token" => $plainTextToken, 'user' => [
    //                 'id' => $user->id,
    //                 'name' => $user->name,
    //                 'email' => $user->email,
    //                 'username' => $user->username,
    //                 'image' => $user->image,
    //             ]]
    //         ]);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //             'data' => null
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'status' => 'error',
    //             'message' => 'Something Wrong'
    //         ], 500);
    //     }
    // }

    //=====================LOGOUT==================
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => "Logout Successfully"]);
    }

    // public function changePassword(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'current_password' => 'required',
    //             'new_password' => 'required|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$!%*?&])[A-Za-z\d@#$!%*?&]+$/',
    //         ]);
    //         $user = $request->user();
    //         if (!Hash::check($request->current_password, $user->password)) {
    //             return response()->json([
    //                 'message' => 'Current password is incorrect',
    //                 'success' => false,
    //             ], 400);
    //         }
    //         $user->update([
    //             'password' => Hash::make($request->new_password),
    //         ]);
    //         return response()->json([
    //             'message' => 'Password updated successfully',
    //             'success' => true,
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
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Traits\ApiResponseTrait;

class UserController extends Controller
{
    use ApiResponseTrait;
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
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse("User Not Found", 404);
            }
            return $this->successResponse("Successfully Displaying Data", $this->formatUserResponse($user));
        } catch (\Exception $e) {
            return $this->errorResponse("Error Updating Data: " . $e->getMessage(), 500);
        }
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

    public function update(Request $request)
    {
        try {
            $user = $request->user();
            $validatedData = $request->validate([
                'name' => 'nullable|string',
                'image' => 'nullable|string',
                'aboutme' => 'nullable|string|max:1000',
                'current_password' => 'required_with:new_password',
                'new_password' => 'min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$!%*?&])[A-Za-z\d@#$!%*?&]+$/',
            ]);
            if (!empty($validatedData['current_password']) && !empty($validatedData['new_password'])) {
                if (!Hash::check($validatedData['current_password'], $user->password)) {
                    return $this->errorResponse("Correct Password Is Incorrect", 400);
                }
                $user->password = Hash::make($validatedData['new_password']);
            }

            $user->fill($validatedData);
            $user->save();
            return $this->successResponse("Updated Success", $this->formatUserResponse($user));
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse("Error Creating Data: " . $e->getMessage(), 500);
        };
    }
    //

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
            $hours = (int) 4;
            $plainTextToken = $newUser->createToken($newUser->email, ['*'], now()->addHours($hours))->plainTextToken;
            return $this->successResponse("Login Successfully", [
                'user' => $this->formatUserResponse($newUser),
                'token' => $plainTextToken
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse("Error Creating Data: " . $e->getMessage(), 500);
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
            return $this->errorResponse("Invalid Credentials or Account Disable", 400);
        }

        $user = Auth::user();
        $user->tokens()->delete();
        $hours = (int) 4;
        $plainTextToken = $user->createToken($user->email, ['*'], now()->addHours($hours))->plainTextToken;
        $expiresAt = now()->addHours($hours)->toDateTimeString();

        return $this->successResponse("Login Successfully", [
            'user' => $this->formatUserResponse($user),
            'token' => $plainTextToken,
            'expiresToken' => $expiresAt
        ]);
    }


    //=====================LOGOUT==================
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return $this->successResponse("Logout Success", 200);
    }
}

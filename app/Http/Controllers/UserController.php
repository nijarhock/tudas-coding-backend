<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = User::orderBy($request->orderBy[0], $request->orderBy[1])
                        ->where("name", "like", "%".$request->search."%")
                        ->orWhere("email", "like", "%".$request->search."%")
                        ->get();

            $perPage = 10;
            $currentPage = $request->pages ?? 1;
            $pagination = new LengthAwarePaginator(
                collect($user->slice($currentPage, $perPage))->values(),
                $user->count(),
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'All Users',
                'data'    => $pagination
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'data'      => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // set validation
            $validator = Validator::make($request->all(), [
                'name'      => 'required',
                'email'     => 'required|email|unique:users',
                'password'  => 'required|min:6|confirmed'
            ]);

            // if validation fail
            if($validator->fails()) {
                return response()->json([
                    'success'   => false,
                    'message'   => $validator->errors(),
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            //create user
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => bcrypt($request->password)
            ]);

            //return response JSON user is created
            if($user) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfuly Created User',
                    'data'    => $user
                ], JsonResponse::HTTP_CREATED);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'data'      => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'success'   => true,
                'message'   => "Show User",
                'data'      => $user,
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'data'      => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
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
    public function update(Request $request, string $id)
    {
        try {
            // set validation
            $validator = Validator::make($request->all(), [
                'name'      => 'required',
                'email'     => ['required', 'email', Rule::unique('users')->ignore($id) ],
                'password'  => 'required|min:6|confirmed'
            ]);

            // if validation fail
            if($validator->fails()) {
                return response()->json([
                    'data'      => [],
                    'message'   => $validator->errors(),
                    'success'   => false
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            //edit user
            $user = User::findOrFail($id);
            $user->update([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => bcrypt($request->password)
            ]);

            //return response JSON user is edited
            if($user) {
                return response()->json([
                    'success' => true,
                    'message' => 'User Update Successfuly',
                    'data'    => $user
                ], JsonResponse::HTTP_OK);
            }

        } catch (\Exception $e) {
            return response()->json([
                'data'      => [],
                'success'   => false,
                'message'   => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // delete user
            $user = User::findOrFail($id)->delete();

            //return response JSON user is deleted
            if($user) {
                return response()->json([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ], JsonResponse::HTTP_OK);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

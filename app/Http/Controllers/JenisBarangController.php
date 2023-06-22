<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JenisBarang;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class JenisBarangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function all()
    { 
        try {
            $jenisBarang = JenisBarang::all();

            return response()->json([
                'success' => true,
                'message' => 'All Jenis Barang',
                'data'    => $jenisBarang
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'data'      => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index(Request $request)
    {
        try {
            $jenisBarang = JenisBarang::orderBy($request->orderBy[0], $request->orderBy[1])
                        ->where("nama", "like", "%".$request->search."%")
                        ->orWhere("deskripsi", "like", "%".$request->search."%")
                        ->get();

            $perPage = 10;
            $currentPage = $request->pages ?? 1;
            $pagination = new LengthAwarePaginator(
                collect($jenisBarang->slice($currentPage, $perPage))->values(),
                $jenisBarang->count(),
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'All Jenis Barang',
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // set validation
            $validator = Validator::make($request->all(), [
                'nama'      => 'required|unique:jenis_barang',
                'deskripsi' => 'max:255'
            ]);

            // if validation fail
            if($validator->fails()) {
                return response()->json([
                    'success'   => false,
                    'message'   => $validator->errors(),
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            //create Jenis Barang
            $jenisBarang = JenisBarang::create([
                'nama'      => $request->nama,
                'deskripsi' => $request->deskripsi
            ]);

            //return response JSON Jenis Barang is created
            if($jenisBarang) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfuly Created Jenis Barang',
                    'data'    => $jenisBarang
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
            $jenisBarang = JenisBarang::findOrFail($id);

            return response()->json([
                'success'   => true,
                'message'   => "Show Jenis Barang",
                'data'      => $jenisBarang,
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // set validation
            $validator = Validator::make($request->all(), [
                'nama'          => ['required', Rule::unique('jenis_barang')->ignore($id)],
                'deskripsi'     => 'max:255'
            ]);

            // if validation fail
            if($validator->fails()) {
                return response()->json([
                    'data'      => [],
                    'message'   => $validator->errors(),
                    'success'   => false
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            //edit Jenis Barang
            $jenisBarang = JenisBarang::findOrFail($id);
            $jenisBarang->update([
                'nama'      => $request->nama,
                'deskripsi' => $request->deskripsi
            ]);

            //return response JSON Jenis Barang is edited
            if($jenisBarang) {
                return response()->json([
                    'success' => true,
                    'message' => 'Jenis Barang Update Successfuly',
                    'data'    => $jenisBarang
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
            // delete Jenis Barang
            $jenisBarang = JenisBarang::findOrFail($id)->delete();

            //return response JSON Jenis Barang is deleted
            if($jenisBarang) {
                return response()->json([
                    'success' => true,
                    'message' => 'Jenis Barang deleted successfully'
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

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Barang;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class BarangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $barang = Barang::orderBy($request->orderBy[0], $request->orderBy[1])
                        ->where("nama", "like", "%".$request->search."%")
                        ->orWhere("satuan", "like", "%".$request->search."%")
                        ->get();

            $perPage = 10;
            $currentPage = $request->pages ?? 1;
            $pagination = new LengthAwarePaginator(
                collect($barang->slice($currentPage, $perPage))->values(),
                $barang->count(),
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'All Barang',
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
                'nama'            => 'required|unique:barang',
                'jenis_barang_id' => 'required|exists:App\Models\JenisBarang,id',
                'satuan'          => 'required',
                'stok'            => 'required|integer',
                'harga'           => 'required|integer',
                'gambar'          => 'required|image|max:2048',
                'deskripsi'       => 'max:255'
            ]);

            // if validation fail
            if($validator->fails()) {
                return response()->json([
                    'success'   => false,
                    'message'   => $validator->errors(),
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($request->hasFile('gambar')) {
                $image = $request->file('gambar');
                $filename = time() . '_' . $image->getClientOriginalName();
                $image->storeAs('public/images', $filename);
            }

            //create Barang
            $barang = Barang::create([
                'nama'                 => $request->nama,
                'jenis_barang_id'      => $request->jenis_barang_id,
                'satuan'               => $request->satuan,
                'stok'                 => $request->stok,
                'harga'                => $request->harga,
                'gambar'               => url("/")."/storage/images/".$filename,
                'deskripsi'            => $request->deskripsi
            ]);

            //return response JSON Barang is created
            if($barang) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfuly Created Barang',
                    'data'    => $barang
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
            $barang = Barang::findOrFail($id);

            return response()->json([
                'success'   => true,
                'message'   => "Show Barang",
                'data'      => $barang,
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
    public function update(string $id, Request $request)
    {
        try {
            // set validation
            $validator = Validator::make($request->all(), [
                'nama'            => ['required', Rule::unique('barang')->ignore($id)],
                'jenis_barang_id' => 'required|exists:App\Models\JenisBarang,id',
                'satuan'          => 'required',
                'stok'            => 'required|integer',
                'harga'           => 'required|integer',
                'deskripsi'       => 'max:255'
            ]);

            // if validation fail
            if($validator->fails()) {
                return response()->json([
                    'data'      => [],
                    'message'   => $validator->errors(),
                    'success'   => false
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // edit Barang
            // if image change
            $barang = Barang::findOrFail($id);
            if ($request->hasFile('gambar')) {
                $validator = Validator::make($request->only('gambar'), [
                    'gambar'   => 'image|max:2048'
                ]);
    
                // if validation fail
                if($validator->fails()) {
                    return response()->json([
                        'data'      => [],
                        'message'   => $validator->errors(),
                        'success'   => false
                    ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                }

                if($barang->gambar) {
                    Storage::delete('public/images/' . str_replace(url("/"), "", $barang->gambar));
                }

                $image = $request->file('gambar');
                $filename = time() . '_' . $image->getClientOriginalName();
                $image->storeAs('public/images', $filename);

                $barang->update([
                    'nama'              => $request->nama,
                    'jenis_barang_id'   => $request->jenis_barang_id,
                    'satuan'            => $request->satuan,
                    'stok'              => $request->stok,
                    'harga'             => $request->harga,
                    'gambar'            => url("/")."/storage/images/".$filename,
                    'deskripsi'         => $request->deskripsi
                ]);
            } 

            $barang->update([
                'nama'              => $request->nama,
                'jenis_barang_id'   => $request->jenis_barang_id,
                'satuan'            => $request->satuan,
                'stok'              => $request->stok,
                'harga'             => $request->harga,
                'deskripsi'         => $request->deskripsi
            ]);

            //return response JSON Jenis Barang is edited
            if($barang) {
                return response()->json([
                    'success' => true,
                    'message' => 'Barang Update Successfuly',
                    'data'    => $barang
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
            // delete Barang
            $barang = Barang::findOrFail($id)->delete();

            //return response JSON Barang is deleted
            if($barang) {
                return response()->json([
                    'success' => true,
                    'message' => 'Barang deleted successfully'
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

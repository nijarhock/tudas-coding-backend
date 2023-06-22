<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\Barang;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TransaksiController extends Controller
{
    public function store()
    {
        try {
            $user_id = JWTAuth::parseToken()->getPayload()->get('sub');
            $transaksi = Transaksi::where("user_id", $user_id)
                                    ->whereNot("status", "selesai");

            // if kasir dont have transaksi
            if($transaksi->count() === 0) {
                $new_id = Transaksi::where("tanggal_transaksi", date("Y-m-d"))->count() + 1;
                $transaksi = Transaksi::create([
                    "user_id"           => $user_id,
                    "kode_invoice"      => date("Ymd").substr_replace("0000000", $new_id, -1 * strlen($new_id)),
                    "tanggal_transaksi" => date("Y-m-d"),
                    "total_transaksi"   => 0,
                    "bayar"             => 0,
                    "kembalian"         => 0,
                    "status"            => "proses"
                ]);
            }

            $transaksi = Transaksi::where("user_id", $user_id)
                                    ->whereNot("status", "selesai")
                                    ->first();

            return response()->json([
                'success'   => true,
                'message'   => "Data Transaksi",
                'data'      => $transaksi,
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'data'      => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function addBarang(Request $request, string $id)
    {
        try {
            // set validation
            $validator = Validator::make($request->all(), [
                'barang_id'     => 'required|integer|exists:App\Models\Barang,id',
                'qty'           => 'required|integer'
            ]);

            // if validation fail
            if($validator->fails()) {
                return response()->json([
                    'success'   => false,
                    'message'   => $validator->errors(),
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $transaksi = Transaksi::findOrFail($id);

            // cek transaksi masih proses
            if($transaksi->status !== "proses") {
                return response()->json([
                    'success'   => false,
                    'message'   => "Transaksi sudah lunas atau selesai, buat transaksi baru",
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            // cek stok barang masih cukup
            $barang = Barang::findOrFail($request->barang_id);
            if($barang->stok < $request->qty) {
                return response()->json([
                    'success'   => false,
                    'message'   => "Stok barang tidak mencukupi",
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // tambah detail transaksi
            if($detail = TransaksiDetail::where("transaksi_id", $id)->where("barang_id", $request->barang_id)->first())
            {
                $detail->update([
                    "qty"           => $detail->qty + $request->qty,
                    "total"         => $barang->harga * ($detail->qty + $request->qty)
                ]);

                $qty_akhir = $detail->qty + $request->qty;
            } 
            else
            {
                TransaksiDetail::create([
                    "transaksi_id"  => $id,
                    "barang_id"     => $request->barang_id,
                    "qty"           => $request->qty,
                    "harga"         => $barang->harga,
                    "total"         => $barang->harga * $request->qty
                ]);

                $qty_akhir = $request->qty;
            }

            // update stok barang
            $barang->update([
                "stok"  => $barang->stok - $qty_akhir
            ]);

            // update total transaksi
            $transaksi->update([
                "total_transaksi" => TransaksiDetail::where("transaksi_id", $id)->sum("total")
            ]);

            return response()->json([
                'success'   => true,
                'message'   => "Berhasil Tambah Barang",
                'data'      => $transaksi,
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'data'      => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteBarang(string $id)
    {
        try {
            $detail = TransaksiDetail::findOrFail($id);
            $barang = Barang::findOrFail($detail->barang_id);
            $transaksi = Transaksi::findOrFail($detail->transaksi_id);

            // cek transaksi masih proses
            if($transaksi->status !== "proses") {
                return response()->json([
                    'success'   => false,
                    'message'   => "Transaksi sudah lunas atau selesai, buat transaksi baru",
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // update stok barang
            $barang->update([
                "stok"  => $barang->stok + $detail->qty
            ]);
            
            $detail->delete();

            // update total transaksi
            $transaksi->update([
                "total_transaksi" => TransaksiDetail::where("transaksi_id", $transaksi->id)->sum("total")
            ]);
            

            return response()->json([
                'success'   => true,
                'message'   => "Berhasil Delete Barang",
                'data'      => $transaksi,
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'data'      => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function prosesPembayaran(Request $request, $id)
    {
        try {
            // set validation
            $validator = Validator::make($request->all(), [
                'bayar'     => 'required|integer'
            ]);

            // if validation fail
            if($validator->fails()) {
                return response()->json([
                    'success'   => false,
                    'message'   => $validator->errors(),
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // jika transaksi masih kosong
            if(TransaksiDetail::where("transaksi_id", $id)->count() === 0) {
                return response()->json([
                    'success'   => false,
                    'message'   => "Transaksi masih kosong",
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            // if bayar < total transaksi
            $transaksi = Transaksi::findOrFail($id);
            if($request->bayar < $transaksi->total_transaksi) {
                return response()->json([
                    'success'   => false,
                    'message'   => "Pembayaran kurang dari nilai transaksi",
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // ubah transaksi ke lunas
            $kembalian = $request->bayar - $transaksi->total_transaksi;
            $transaksi->update([
                "bayar"     => $request->bayar,
                "kembalian" => $kembalian,
                "status"    => "lunas"
            ]);

            return response()->json([
                'success'   => true,
                'message'   => "Berhasil melakukan pembayaran",
                'data'      => $transaksi
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'data'      => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function selesaiTransaksi(string $id) 
    {
        try {
            $transaksi = Transaksi::findOrFail($id);

            // jika transaksi belum lunas
            if($transaksi->status !== "lunas") {
                return response()->json([
                    'success'   => false,
                    'message'   => "Transaksi belum lunas silahkan melakukan pembayaran",
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $transaksi->update([
                "status"    => "selesai"
            ]);

            return response()->json([
                'success'   => true,
                'message'   => "Berhasil menyelesaikan transaksi",
                'data'      => $transaksi
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'data'      => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function laporanPenjualan(Request $request)
    {
        try {
            $orderBy = $request->orderBy[0];
            if($orderBy == "nama") {
                $orderBy = "barang.nama";
            } else if($orderBy == "tanggal_transaksi") {
                $orderBy = "transaksi.tanggal_transaksi";
            } else {
                $orderBy = "jenis_barang.nama";
            }
            $detail = TransaksiDetail::
                        join('transaksi', 'transaksi.id', '=', 'transaksi_detail.transaksi_id')
                        ->join('barang', 'barang.id', '=', 'transaksi_detail.barang_id')
                        ->join('jenis_barang', 'barang.jenis_barang_id', '=', 'jenis_barang.id')
                        ->where('barang.nama', 'like', '%'.$request->search.'%')
                        ->where('jenis_barang.nama', 'like', '%'.$request->search.'%')
                        ->select('barang.nama', 'barang.stok', 'transaksi_detail.qty', 'transaksi_detail.qty', 'transaksi.tanggal_transaksi', 'jenis_barang.nama as jenis_barang')
                        ->orderBy($orderBy, $request->orderBy[1])
                        ->get();

            return response()->json([
                'success'   => true,
                'message'   => "Laporan Penjualan",
                'data'      => $detail
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'data'      => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function laporanPenjualanJenis(Request $request)
    {
        try {
            $detail = TransaksiDetail::
                        join('transaksi', 'transaksi.id', '=', 'transaksi_detail.transaksi_id')
                        ->join('barang', 'barang.id', '=', 'transaksi_detail.barang_id')
                        ->join('jenis_barang', 'barang.jenis_barang_id', '=', 'jenis_barang.id')
                        ->selectRaw('jenis_barang.nama, COUNT(transaksi_detail.id) as jumlah_transaksi, SUM(transaksi_detail.qty) as jumlah_qty, SUM(transaksi_detail.total) as jumlah_penjualan')
                        ->where("tanggal_transaksi", ">=", $request->awal)
                        ->where("tanggal_transaksi", "<=", $request->akhir)
                        ->groupBy('jenis_barang.nama')
                        ->orderBy('jumlah_transaksi', 'DESC')
                        ->get();

            return response()->json([
                'success'   => true,
                'message'   => "Laporan Penjualan Jenis",
                'data'      => $detail
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage(),
                'data'      => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

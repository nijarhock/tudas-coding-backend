<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\JenisBarangController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', App\Http\Controllers\Api\LoginController::class)->name('login');

Route::group(['middleware' => ['auth:api']], function () {
    Route::resource('/jenis_barang', JenisBarangController::class);
    Route::resource('/barang', BarangController::class);
    Route::resource('/user', UserController::class);
    Route::get('/all_jenis', [JenisBarangController::class, "all"]);

    Route::post('/new_transaksi', [TransaksiController::class, "store"])->name('new_transaksi');
    Route::put('/add_barang/{id}', [TransaksiController::class, "addBarang"])->name('add_barang');
    Route::delete('/delete_barang/{id}', [TransaksiController::class, "deleteBarang"])->name('delete_barang');
    Route::put('/proses_pembayaran/{id}', [TransaksiController::class, "prosesPembayaran"])->name('proses_pembayaran');
    Route::put('/selesai_transaksi/{id}', [TransaksiController::class, "selesaiTransaksi"])->name('selesai_transaksi');

    Route::get('/laporan_penjualan', [TransaksiController::class, "laporanPenjualan"]);
    Route::get('/laporan_penjualan_jenis', [TransaksiController::class, "laporanPenjualanJenis"]);
});

Route::post('/logout', App\Http\Controllers\Api\LogoutController::class)->name('logout');


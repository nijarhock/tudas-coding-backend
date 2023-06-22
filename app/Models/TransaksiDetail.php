<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiDetail extends Model
{
    use HasFactory;

    protected $table = "transaksi_detail";

    protected $fillable = [
        'transaksi_id',
        'barang_id',
        'qty',
        'harga',
        'total'
    ];

    protected $with = ['barang'];

    public function transaksi() {
        return $this->belongsTo('App\Models\Transaksi', 'transaksi_id');
    }

    public function barang() {
        return $this->belongsTo('App\Models\Barang', 'barang_id');
    }
}

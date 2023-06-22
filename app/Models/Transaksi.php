<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = "transaksi";

    protected $fillable = [
        'user_id',
        'kode_invoice',
        'tanggal_transaksi',
        'total_transaksi',
        'bayar',
        'kembalian',
        'status'
    ];

    protected $with = ['transaksi_detail'];

    public function transaksi_detail() {
        return $this->hasMany('App\Models\TransaksiDetail');
    }
}

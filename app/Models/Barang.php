<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = "barang";

    protected $fillable = [
        'nama',
        'jenis_barang_id',
        'satuan',
        'stok',
        'harga',
        'gambar',
        'deskripsi'
    ];

    protected $with = ['jenis_barang'];

    public function jenis_barang() {
        return $this->belongsTo('App\Models\JenisBarang', 'jenis_barang_id');
    }
}

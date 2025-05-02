<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kabupaten extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $primaryKey = 'kode_kabupaten';

    protected $keyType = 'string';

    protected $fillable = [''];

    public function provinsi() {
        return $this->belongsTo(Provinsi::class, 'kode_provinsi', 'kode_provinsi');
    }

    public function kecamatans() {
        return $this->hasMany(Kecamatan::class, 'kode_kabupaten', 'kode_kabupaten');
    }
}

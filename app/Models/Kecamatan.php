<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kecamatan extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $primaryKey = 'kode_kecamatan';

    protected $keyType = 'string';

    protected $fillable = [''];

    public function kabupaten() {
        return $this->belongsTo(Kabupaten::class, 'kode_kabupaten', 'kode_kabupaten');
    }

    public function desas() {
        return $this->hasMany(Desa::class, 'kode_kecamatan', 'kode_kecamatan'); 
    }
    
}

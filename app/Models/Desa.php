<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Desa extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    
    protected $primaryKey = 'kode_desa';

    protected $keyType = 'string';

    protected $guarded = [''];

    public function kecamatan() {
        return $this->belongsTo(Kecamatan::class, 'kode_kecamatan', 'kode_kecamatan');
    }

    public function projects() {
        return $this->hasMany(Project::class, 'kode_desa', 'kode_desa');
    }
}

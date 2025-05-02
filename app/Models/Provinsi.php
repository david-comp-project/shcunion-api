<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provinsi extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $primaryKey = 'kode_provinsi';

    protected $keyType = 'string';

    protected $fillable = [''];


    public function kabupatens() {
        return $this->hasMany(Kabupaten::class, 'kode_provinsi', 'kode_provinsi');
    }
}

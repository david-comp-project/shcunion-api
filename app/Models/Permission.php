<?php
namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'uuid';


    protected $guarded = ['uuid'];

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

    }
}
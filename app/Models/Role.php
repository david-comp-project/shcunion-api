<?php
namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends SpatieRole
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
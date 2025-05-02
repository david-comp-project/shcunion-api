<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SocialMedia extends Model
{
    use HasUuids,SoftDeletes;

    protected $table = 'social_medias';

    protected $dates = ['deleted_at'];

    protected $guarded = ['social_media_id'];

    protected $primaryKey = 'social_media_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->social_media_id = Str::uuid();
        });

    }

    public function projectShares() {
        return $this->hasMany(ProjectShare::class, 'social_media_id', 'social_media_id');
    }
}

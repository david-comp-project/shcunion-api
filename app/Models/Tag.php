<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = ['tag_id'];

    protected $primaryKey = 'tag_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public function projectTags() { // Hubungkan ke ProjectTag, bukan Project
        return $this->hasMany(ProjectTag::class, 'tag_id', 'tag_id');
    }

    public static function booted() {
        static::creating(function ($model) {
            $model->tag_id = Str::uuid();
        });
    }
}

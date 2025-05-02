<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectShare extends Model
{
    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = ['project_share_id'];

    protected $primaryKey = 'project_share_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->project_share_id = Str::uuid();
        });

    }

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function sosmed() {
        return $this->belongsTo(SocialMedia::class, 'social_media_id', 'social_media_id');
    }
}

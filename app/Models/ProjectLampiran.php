<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectLampiran extends Model
{

    use SoftDeletes;

    protected $dates = ['deleted_at'];
    
    protected $guarded = ['project_lampiran_id'];

    protected $primaryKey = 'project_lampiran_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public static function booted() {
        static::creating(function($model) {
            $model->project_lampiran_id = Str::uuid();
        });
    }

    public function uploader() {
        return $this->belongsTo(User::class, 'uploader_id', 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTag extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = ['project_tag_id'];

    protected $primaryKey = 'project_tag_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function tag() { // Gunakan singular "tag()" karena relasinya belongsTo
        return $this->belongsTo(Tag::class, 'tag_id', 'tag_id');
    }

    public static function booted() {
        static::creating(function ($model) {
            $model->project_tag_id = Str::uuid();
        });
    }
}

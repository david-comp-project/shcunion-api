<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectComment extends Model
{
    use HasUuids, SoftDeletes;

    protected $primaryKey = 'project_comment_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['project_comment_id'];

    public static function booted() {
        static::creating(function ($model) {
            $model->project_comment_id = Str::uuid();
        });

    }
    

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // ✅ Relasi ke Parent Comment
    public function parent()
    {
        return $this->belongsTo(ProjectComment::class, 'project_comment_parent_id', 'project_comment_id');
    }

    // ✅ Relasi ke Child Comments (Balasan)
    public function replies()
    {
        return $this->hasMany(ProjectComment::class, 'project_comment_parent_id', 'project_comment_id');
    }

    protected function dateFormat(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at
                ? $this->created_at->translatedFormat('d M Y')
                : null
                
        );
    }

    protected function timeFormat(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at
                ? $this->created_at->translatedFormat('H:i')
                : null
                
        );
    }

}

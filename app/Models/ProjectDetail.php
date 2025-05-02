<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectDetail extends Model
{

    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $guarded = ['project_detail_id'];

    protected $primaryKey = 'project_detail_id';

    protected $keyType = 'string';

    public $incrementing = false;


    public static function booted() {
        static::creating(function ($model) {
            $model->project_detail_id = Str::uuid();
        });

        Carbon::setLocale('id');

    }

    public function donatur() {
        return $this->belongsTo(User::class, 'donatur_id', 'user_id');
    }

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    
}

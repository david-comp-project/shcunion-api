<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VolunteerDetail extends Model
{
    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];


    protected $guarded = ['volunteer_detail_id'];

    protected $primaryKey = 'volunteer_detail_id';

    protected $keyType = 'string';

    public $incrementing = false;


    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function volunteer() {
        return $this->belongsTo(User::class, 'volunteer_id', 'user_id');
    }

    public static function booted() {
        static::creating(function ($model) {
            $model->volunteer_detail_id = Str::uuid();
        });
    }

    
}

<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Icon extends Model
{
    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $primaryKey = 'icon_id';

    protected $guarded = ['icon_id'];

    protected $keyType = 'string';

    public $incrementing = false;


    public static function booted() {
        static::creating(function ($model) {
            $model->icon_id = Str::uuid();
        });

    }

    public function projectTimelineDetails() {
        return $this->hasMany(ProjectTimelineDetail::class, 'icon_id', 'icon_id');
    }
    
}

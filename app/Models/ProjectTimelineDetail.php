<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectTimelineDetail extends Model
{
    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];
    
    protected $primaryKey = 'project_timeline_detail_id';

    protected $guarded = ['project_timeline_detail_id'];

    protected $keyType = 'string';

    public $incrementing = false;


    public static function booted() {
        static::creating(function ($model) {
            $model->project_timeline_detail_id = Str::uuid();
        });

        Carbon::setlocale('id');

    }

    protected function timeFormat(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['time']
        );
    }

    public function projectTimeline() {
        return $this->belongsTo(ProjectTimeline::class, 'project_timeline_id', 'project_timeline_id');
    }

    public function icon() {
        return $this->belongsTo(Icon::class, 'icon_id', 'icon_id');
    }
    
}

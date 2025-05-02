<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectTimeline extends Model
{
    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $primaryKey = 'project_timeline_id';

    protected $guarded = ['project_timeline_id'];

    protected $keyType = 'string';

    public $incrementing = false;


    public static function booted() {
        static::creating(function ($model) {
            $model->project_timeline_id = Str::uuid();
        });

        Carbon::setLocale('id');

    }

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function projectTimelineDetails() {
        return $this->hasMany(ProjectTimelineDetail::class, 'project_timeline_id', 'project_timeline_id');
    }

    protected function dateFormat(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->timeline_date
                ? [Carbon::parse($this->timeline_date)->translatedFormat('d'), Carbon::parse($this->timeline_date)->translatedFormat('F')]
                : null
        );
    }

    protected function dateFormatFull(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->timeline_date
                ? Carbon::parse($this->timeline_date)->translatedFormat('Y-m-d')
                : null
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReportCase extends Model
{
    use HasUuids,SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = ['report_case_id'];

    protected $primaryKey = 'report_case_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->report_case_id = Str::uuid();
        });

    }

    protected function dateFormat(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at
                ? $this->created_at->translatedFormat('d M Y')
                : null
                
        );
    }

    public function reporter() {
        return $this->belongsTo(User::class, 'reporter_id', 'user_id');
    }

    public function reported() {
        return $this->belongsTo(User::class, 'reported_id', 'user_id');
    }

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}

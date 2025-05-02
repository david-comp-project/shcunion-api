<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VolunteerInvolvement extends Model
{
    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = ['volunteer_involvement_id'];

    protected $primaryKey = 'volunteer_involvement_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->volunteer_involvement_id = Str::uuid();
        });

    }

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function volunteer() {
        return $this->belongsTo(User::class, 'volunteer_id', 'user_id');
    }

    protected function dateFormat(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at
                ? $this->created_at->translatedFormat('d M Y')
                : null
                
        );
    }
}

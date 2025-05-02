<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectEvaluasi extends Model
{
    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $primaryKey = 'project_evaluasi_id';

    protected $guarded = ['project_evaluasi_id'];

    protected $keyType = 'string';

    public $incrementing = false;


    public static function booted() {
        static::creating(function ($model) {
            $model->project_evaluasi_id = Str::uuid();
        });

        Carbon::setlocale('id');

    }

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function getHour() : Attribute {
        return Attribute::make(
            get: fn () => $this->created_at ? $this->created_at->translatedFormat('H.i') : null
        );
    }
    
    public function evaluator() {
        return $this->belongsTo(User::class, 'evaluator_id', 'user_id');
    }

    // Accessor untuk warna status
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'approved' => 'bg-green-100 text-green-700',
            'review' => 'bg-gray-100 text-gray-700',
            'rejected' => 'bg-red-100 text-red-700',
            default => 'bg-yellow-100 text-yellow-700',
        };
    }


    public function getTagColorAttribute() {                     
    return match ($this->tag_component) {
        'image' => 'bg-blue-100 text-blue-700',
        'title' => 'bg-green-100 text-green-700',
        'description' => 'bg-yellow-100 text-yellow-700',
        'point' => 'bg-purple-100 text-purple-700',
        'address' => 'bg-red-100 text-red-700',
        'tag' => 'bg-indigo-100 text-indigo-700',
        'file attachment' => 'bg-gray-100 text-gray-700',
        'timelines' => 'bg-pink-100 text-pink-700',
        'date' => 'bg-orange-100 text-orange-700',
        'amount' => 'bg-teal-100 text-teal-700',
        default => 'bg-gray-100 text-gray-700',
    };
}


    
}

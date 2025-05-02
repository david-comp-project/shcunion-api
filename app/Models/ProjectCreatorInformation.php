<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectCreatorInformation extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'project_creator_informations';

    protected $dates = ['deleted_at'];

    protected $guarded = ['project_creator_information_id'];

    protected $primaryKey = 'project_creator_information_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->project_creator_information_id = Str::uuid();
        });
    }

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}

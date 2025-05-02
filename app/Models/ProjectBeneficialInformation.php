<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectBeneficialInformation extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'project_beneficial_informations';

    protected $dates = ['deleted_at'];

    protected $guarded = ['project_beneficial_information_id'];

    protected $primaryKey = 'project_beneficial_information_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->project_beneficial_information_id = Str::uuid();
        });

    }

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

}

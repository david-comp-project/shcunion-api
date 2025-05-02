<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserDetail extends Model
{
    use HasUuids, SoftDeletes;

    protected $primaryKey = 'user_detail_id';

    protected $guarded = ['user_detail_id'];

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->user_detail_id = Str::uuid();
        });
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}

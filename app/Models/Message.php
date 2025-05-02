<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Message extends Model
{
    use HasUuids;

    protected $guarded = ['message_id'];

    protected $primaryKey = 'message_id';

    protected $keyType = 'string';

    public $incrementing = false;
    
    public static function booted() {
        static::creating(function ($model) {
            $model->message_id = Str::uuid();
        });
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}

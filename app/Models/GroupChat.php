<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class GroupChat extends Model

{
    use HasUuids;

    protected $guarded = ['group_chat_id'];

    protected $primaryKey = 'group_chat_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->group_chat_id = Str::uuid();
        });

    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'users_group_chats', 'group_chat_id', 'user_id');
    }

    public function messageGroupChat() {
        return $this->hasMany(MessageGroupChat::class, 'group_chat_id', 'group_chat_id');
    }

    public function project() {
        return $this->hasOne(Project::class, 'project_id', 'project_id');
    }
}

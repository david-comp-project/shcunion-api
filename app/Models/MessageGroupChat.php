<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MessageGroupChat extends Model
{

    use HasUuids;

    protected $guarded = ['message_group_chat_id'];
    
    protected $primaryKey = 'message_group_chat_id';

    protected $keyType = 'string';

    public $incrementing = false;

    // public function groupChats() {
    //     return $this->hasMany(GroupChat::class, 'group_chat_id', 'group_chat_id');
    // }

    public function chats() {
        return $this->hasMany(Chat::class, 'chat_id', 'chat_id');
    }

    public function sender() {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    public function groupChats()
    {
        return $this->belongsToMany(GroupChat::class, 'users_group_chats', 'group_chat_id', 'user_id');
    }

    public static function booted() {
        static::creating(function ($model) {
            $model->message_group_chat_id = Str::uuid();

        });

        Carbon::setLocale('id');

    }
    

    protected function dateFormat(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at
                ? $this->created_at->translatedFormat('D d M Y - H.i')
                : null
        );
    }
    
}

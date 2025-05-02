<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MessagePrivateChat extends Model
{
    use HasUuids;

    protected $guarded = ['message_private_chat_id'];

    protected $primaryKey = 'message_private_chat_id';

    protected $keyType = 'string';

    public $incrementing = false;


    public static function booted() {
        static::creating(function ($model) {
            $model->message_private_chat_id = Str::uuid();

        });

        Carbon::setLocale('id');

    }
    

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function sender() {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');

    }

    public function chats() {
        return $this->hasMany(Chat::class, 'chat_id', 'chat_id')->orderBy('created_at', 'desc');
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

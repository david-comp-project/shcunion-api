<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Chat extends Model
{
    use HasUuids;

    protected $primaryKey = 'chat_id'; // Pastikan ini sesuai
    public $incrementing = false;
    protected $keyType = 'string';


    public static function boot() {
        parent::boot(); // Tambahkan ini untuk memastikan parent boot terpanggil
        
        static::creating(function ($model) {
            if (empty($model->chat_id)) {
                $model->chat_id = Str::uuid();
            }
        });
    }

    public function messageGroupChat() {
        return $this->belongsTo(MessageGroupChat::class, 'chat_id', 'chat_id');
    }

}

<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Agenda extends Model
{
    use HasUuids, SoftDeletes;
    //
    protected $guarded = ['agenda_id'];

    protected $primaryKey = 'agenda_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->agenda_id = Str::uuid();
        });

        Carbon::setLocale('id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // protected function createdDate(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn () => $this->created_at
    //             ? $this->created_at->translatedFormat('d M Y')
    //             : null
                
    //     );
    // }


}

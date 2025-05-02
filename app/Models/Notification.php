<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = ['notification_id'];

    protected $primaryKey = 'notification_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $appends = ['formatted_date'];


    public static function booted() {
        static::creating(function ($model) {
            $model->notification_id = Str::uuid();
        });

        Carbon::setLocale('id');
    }

    public function target() {
        return $this->belongsTo(User::class, 'target_id', 'user_id');
    }

    // protected function dateFormat(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn () => $this->created_at
    //             ? $this->created_at->translatedFormat('D d M Y - H.i')
    //             : null
                
    //     );
    // }

    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }
}

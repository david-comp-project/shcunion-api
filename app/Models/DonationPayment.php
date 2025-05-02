<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DonationPayment extends Model
{
    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = ['donation_payment_id'];

    protected $primaryKey = 'donation_payment_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->donation_payment_id = Str::uuid();
        });

    }

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function donatur() {
        return $this->belongsTo(User::class, 'donatur_id', 'user_id');
    }

    public function channelPayment() {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    protected function dateFormat(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at
                ? $this->created_at->translatedFormat('d M Y')
                : null
                
        );
    }
}

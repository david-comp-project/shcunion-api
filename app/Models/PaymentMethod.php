<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PaymentMethod extends Model
{
    use HasUuids, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = ['payment_method_id'];

    protected $primaryKey = 'payment_method_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->payment_method_id = Str::uuid();
        });

    }

    public function donations() {
        return $this->hasMany(DonationPayment::class, 'payment_method_id', 'payment_method_id');
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use App\Enums\UserBadge;
use Illuminate\Support\Str;
use App\Traits\HasFileTrait;
use App\Jobs\VerificationEmailJob;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasUuids, SoftDeletes, HasFileTrait;

    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'full_name', 'profile_picture',
        'email', 'password', 'email_verified_at', 'address', 'social_media',
        'phone_number', 'nik', 'birth_date', 'job',   'jenis_kelamin',
         'profile_cover', 'scan_ktp', 'jabatan', 'organization_name',
         'provider_name',
         'provider_id', 'total_points'
    ];

 
    
    

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'provider_token'

    ];

    protected $primaryKey = 'user_id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

        // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public static function booted() {
        static::creating(function ($model) {
            $model->user_id = Str::uuid();
        });
    }

    public function setProviderTokenAttribute($value){
        return $this->attributes['provider_token'] = Crypt::crypt($value);
    }

    public function getProviderTokenAttribute($value)
    {
        return Crypt::decrypt($value);
    }

    public function getAvatarUrlAttribute()
    {
        return $this->getUrlFile($this->profile_picture);
    }

    public function message() {
        return $this->hasMany(Message::class, 'user_id', 'user_id');
    }

    public function groupChats()
    {
        return $this->belongsToMany(GroupChat::class, 'users_group_chats', 'user_id', 'group_chat_id');
    }

    public function messageGroupChat() {
        return $this->hasMany(MessageGroupChat::class, 'user_id', 'sender_id');
    }

    public function messagePrivateChats() {
        return $this->hasMany(MessagePrivateChat::class,  'user_id', 'user_id');
    }

    protected function userFullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name . ' ' . $this->last_name
        );
    }

    public function getBadge(): UserBadge
    {
        return UserBadge::getBadge($this->total_points);
    }

    public function getBadgeColor(): string
    {
        return $this->getBadge()->getColor();
    }

    protected function userCreated(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at
            ? $this->created_at->translatedFormat('d M Y')
            : null
        );
    }

    public function projectEvaluasis() {
        return $this->hasMany(ProjectEvaluasi::class, 'evaluator_id', 'user_id');
    }

    public function userDetails() {
        return $this->hasOne(UserDetail::class, 'user_id', 'user_id');
    }

    public function projects() {
        return $this->hasMany(Project::class, 'creator_id', 'user_id',);
    }

    public function donations() {
        return $this->hasMany(DonationPayment::class, 'donatur_id', 'user_id');
    }

    public function volunteerInvolvements() {
        return $this->hasMany(VolunteerInvolvement::class, 'volunteer_id', 'user_id');
    }

    public function sendEmailVerificationNotification()
    {
        VerificationEmailJob::dispatch($this);
    }

    public function reportCases() {
        return $this->hasMany(ReportCase::class, 'reporter_id', 'user_id');
    }

    public function reportedCases() {
        return $this->hasMany(ReportCase::class, 'reported_id', 'user_id');
    }

    public function userWithdrawal() {
        return $this->hasMany(WithdrawalDonation::class, 'user_id', 'user_id');
    }

    public function projectLampirans() {
        return $this->hasMany(ProjectLampiran::class, 'uploader_id', 'user_id');
    }

    public function notifications() {
        return $this->hasMany(Notification::class, 'target_id', 'user_id');
    }

    public function agendas() {
        return $this->hasMany(Agenda::class, 'user_id', 'user_id');
    }
}
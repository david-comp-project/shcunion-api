<?php

namespace App\Models;

use App\Traits\HasFiltersTrait;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    //
    use HasUuids, HasFiltersTrait, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = ['project_id'];

    protected $primaryKey = 'project_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function booted() {
        static::creating(function ($model) {
            $model->project_id = Str::uuid();
        });

        Carbon::setLocale('id');
    }

    // protected function casts(): array
    // {
    //     return [
    //         'project_criteria' => 'array',
    //     ];
    // }

    protected function dateFormat(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at
                ? $this->created_at->translatedFormat('D d M Y - H.i')
                : null
                
        );
    }

    protected function createdDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at
                ? $this->created_at->translatedFormat('d M Y')
                : null
                
        );
    }

    protected function startDateFormat(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::parse($this->attributes['project_start_date'])->translatedFormat('d M Y')
        );
    }

    protected function endDateFormat(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::parse($this->attributes['project_end_date'])->translatedFormat('d M Y')
        );
    }

    
    protected function startDateFormatFull(): Attribute
{
    return Attribute::make(
        get: fn () => Carbon::parse($this->attributes['project_start_date'])->format('Y-m-d')
    );
}

protected function endDateFormatFull(): Attribute
{
    return Attribute::make(
        get: fn () => Carbon::parse($this->attributes['project_end_date'])->format('Y-m-d')
    );
}



    public function user() {
        return $this->belongsTo(User::class, 'creator_id', 'user_id');
    }

    public function projectDetails() {
        return $this->hasMany(ProjectDetail::class, 'project_id', 'project_id');
    }

    public function volunteerDetails() {
        return $this->hasMany(VolunteerDetail::class, 'project_id', 'project_id');
    }

    /**
     * Filter berdasarkan status.
     */
    public function filterStatus($query, $value)
    {
        $query->where('project_status', $value);
    }

    /**
     * Filter berdasarkan kategori.
     */
    public function filterCategory($query, $value)
    {
        $query->where('project_category', $value);
    }

    /**
     * Sortir hasil.
     */
    public function filterSort($query, $value)
    {
        $query->orderBy('project_end_date', $value);
    }

    public function filterSearch($query, $value)
    {
        $value = trim(strtolower($value)); // Pastikan lowercase dan tidak ada spasi ekstra

        $query->where('project_description', 'like', '%' . $value . '%')->orWhere('project_title', 'like', '%' . $value . '%');
    }

    public function desa()
    {
        return $this->belongsTo(Desa::class, 'kode_desa', 'kode_desa');
    }

    public function projectEvaluasis() {
        return $this->hasMany(ProjectEvaluasi::class, 'project_id', 'project_id');
    }

    public function projectTimelines() {
        return $this->hasMany(ProjectTimeline::class, 'project_id', 'project_id');
    }

    public function projectLampirans() {
        return $this->hasMany(ProjectLampiran::class, 'project_id', 'project_id');
    }

    public function projectTags() {
        return $this->hasMany(ProjectTag::class, 'project_id', 'project_id');
    }

    public function projectCreatorInformation() {
        return $this->hasOne(ProjectCreatorInformation::class, 'project_id', 'project_id');
    }

    public function projectBeneficialInformation() {
        return $this->hasOne(ProjectBeneficialInformation::class, 'project_id', 'project_id');
    }

    public function projectDonations() {
        return $this->hasMany(DonationPayment::class, 'project_id', 'project_id');
    }

    public function projectVolunteers() {
        return $this->hasMany(VolunteerInvolvement::class, 'project_id', 'project_id');
    }


    public function projectComments() {
        return $this->hasMany(ProjectComment::class, 'project_id', 'project_id');
    }

    public function projectReported() {
        return $this->hasMany(ReportCase::class, 'project_id', 'project_id');
    }

    public function projectWithdrawal() {
        return $this->hasOne(WithdrawalDonation::class, 'project_id', 'project_id');
    }

    public function groupChat() {
        return $this->belongsTo(GroupChat::class, 'project_id', 'project_id');
    }

    public function projectShares() {
        return $this->hasMany(ProjectShare::class, 'project_id', 'project_id');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentUser extends Model
{
    protected $table = 'assessments_user';

    protected $fillable = [
        'coach_id',
        'user_id',
        'weight',
        'height',
        'body_fat',
        'muscle_mass',
        'water_percentage',
        'bmi',
        'visceral_fat',
        'metabolic_age',
        'notes'
    ];

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function measurements()
    {
        return $this->hasOne(MeasurementUser::class, 'assessment_id');
    }

    public function skinfolds()
    {
        return $this->hasOne(SkinfoldUser::class, 'assessment_id');
    }

    public function photos()
    {
        return $this->hasOne(ProgressPhotoUser::class, 'assessment_id');
    }
}

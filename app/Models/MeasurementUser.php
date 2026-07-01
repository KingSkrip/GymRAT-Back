<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementUser extends Model
{

 protected $table = 'measurements_user';
 
    protected $fillable = [
        'assessment_id',
        'neck',
        'shoulders',
        'chest',
        'left_arm',
        'right_arm',
        'waist',
        'abdomen',
        'hip',
        'left_thigh',
        'right_thigh',
        'left_calf',
        'right_calf'
    ];

    public function assessment()
    {
        return $this->belongsTo(AssessmentUser::class);
    }
}
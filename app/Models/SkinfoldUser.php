<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkinfoldUser extends Model
{
    protected $table = 'skinfolds_user';

    protected $fillable = [
        'assessment_id',
        'chest',
        'tricep',
        'subscapular',
        'midaxillary',
        'suprailiac',
        'abdomen',
        'thigh',
        'calf'
    ];

    public function assessment()
    {
        return $this->belongsTo(AssessmentUser::class);
    }
}
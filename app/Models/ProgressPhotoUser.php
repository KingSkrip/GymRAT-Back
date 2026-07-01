<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressPhotoUser extends Model
{
        protected $table = 'progress_photos_user';
    protected $fillable = [
        'assessment_id',
        'front',
        'back',
        'left_side',
        'right_side'
    ];

    public function assessment()
    {
        return $this->belongsTo(AssessmentUser::class);
    }
}
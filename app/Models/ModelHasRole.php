<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelHasRole extends Model
{
    use HasFactory;

    protected $table = 'model_has_roles';

    protected $fillable = [
        'role_id',
        'sub_role_id',
        'model_id',
        'model_type'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function subRole()
    {
        return $this->belongsTo(SubRole::class);
    }

    public function user()
{
    return $this->belongsTo(User::class);
}
}
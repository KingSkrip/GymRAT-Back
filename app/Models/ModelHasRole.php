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
        'model_type',
        'model_id',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function subRole()
    {
        return $this->belongsTo(SubRole::class);
    }

    // Relación polimórfica correcta
    public function model()
    {
        return $this->morphTo();
    }

    // Shortcut directo a User cuando model_type = App\Models\User
    public function user()
    {
        return $this->belongsTo(User::class, 'model_id')
            ->where('model_type', User::class);
    }
}
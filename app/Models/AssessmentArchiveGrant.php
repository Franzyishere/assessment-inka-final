<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentArchiveGrant extends Model
{
    protected $guarded = ['id'];

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }
}

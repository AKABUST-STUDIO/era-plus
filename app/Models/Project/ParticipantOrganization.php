<?php

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Model;

class ParticipantOrganization extends Model
{
    protected $table = 'participant_organizations';

    protected $fillable = [
        'name',
    ];
}

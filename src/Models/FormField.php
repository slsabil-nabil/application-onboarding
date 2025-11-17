<?php

namespace Slsabil\ApplicationOnboarding\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormField extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'options'     => 'array',
        'is_required' => 'boolean',
    ];
}

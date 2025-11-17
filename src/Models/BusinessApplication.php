<?php

namespace Slsabil\ApplicationOnboarding\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessApplication extends Model
{
    use HasFactory;

    protected $table = 'business_applications';

    protected $guarded = [];

    protected $casts = [
        'resubmit_expires_at'               => 'datetime',
        'licenses_paths'                    => 'array',
        'supporting_documents_paths'        => 'array',
        'form_data'                         => 'array',
        'interpolation_required_docs'       => 'array',
        'interpolation_contact_corrections' => 'array',
        'interpolation_uploaded_files'      => 'array',
    ];
}

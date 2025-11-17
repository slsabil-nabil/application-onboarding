<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Slsabil\ApplicationOnboarding\Models\FormField;

class ApplicationOnboardingFormFieldsSeeder extends Seeder
{
    public function run(): void
    {
        $order = 1;

        $fields = [
            // 1) Business Information (heading)
            [
                'label'          => 'Business Information',
                'name'           => 'business_information_heading',
                'type'           => 'heading',
                'maps_to_column' => null,
                'is_required'    => false,
                'options'        => null,
            ],

            // 2) Business Name
            [
                'label'          => 'Business Name',
                'name'           => 'business_name',
                'type'           => 'text',
                'maps_to_column' => 'business_name',
                'is_required'    => true,
                'options'        => null,
            ],

            // 3) Industry Type (list)
            [
                'label'          => 'Industry Type',
                'name'           => 'industry_type',
                'type'           => 'list',
                'maps_to_column' => 'industry_type',
                'is_required'    => true,
                'options'        => [
                    'medical'                      => 'Medical',
                    'hair_salon'                   => 'Hair Salon',
                    'car_and_automobile_services'  => 'Car and Automobile Services',
                    'it'                           => 'IT',
                    'other'                        => 'Other',
                ],
            ],

            // 4) Owner’s Details (heading)
            [
                'label'          => "Owner's Details",
                'name'           => 'owners_details_heading',
                'type'           => 'heading',
                'maps_to_column' => null,
                'is_required'    => false,
                'options'        => null,
            ],

            // 5) Full Name
            [
                'label'          => 'Full Name',
                'name'           => 'owner_name',
                'type'           => 'text',
                'maps_to_column' => 'owner_name',
                'is_required'    => true,
                'options'        => null,
            ],

            // 6) Business Email
            [
                'label'          => 'Business Email',
                'name'           => 'owner_email',
                'type'           => 'email',
                'maps_to_column' => 'owner_email',
                'is_required'    => true,
                'options'        => null,
            ],

            // 7) Phone Number
            [
                'label'          => 'Phone Number',
                'name'           => 'owner_phone',
                'type'           => 'tel',
                'maps_to_column' => 'owner_phone',
                'is_required'    => true,
                'options'        => null,
            ],

            // 8) Legal Documents (heading)
            [
                'label'          => 'Legal Documents',
                'name'           => 'legal_documents_heading',
                'type'           => 'heading',
                'maps_to_column' => null,
                'is_required'    => false,
                'options'        => null,
            ],

            // 9) Business License(s)
            [
                'label'          => 'Business License(s)',
                'name'           => 'business_licenses',
                'type'           => 'file',
                'maps_to_column' => null,
                'is_required'    => false,
                'options'        => null,
            ],

            // 10) Supporting Documents
            [
                'label'          => 'Supporting Documents',
                'name'           => 'supporting_documents',
                'type'           => 'file',
                'maps_to_column' => null,
                'is_required'    => false,
                'options'        => null,
            ],
        ];

        foreach ($fields as $field) {
            FormField::updateOrCreate(
                ['name' => $field['name']],
                [
                    'label'          => $field['label'],
                    'type'           => $field['type'],
                    'maps_to_column' => $field['maps_to_column'],
                    'is_required'    => $field['is_required'],
                    'options'        => $field['options'],
                    'order'          => $order++,
                ]
            );
        }
    }
}

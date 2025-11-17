<?php

return [

    // مسار النموذج العام (public form)
    'public_prefix' => 'apply',

    // مسار الاستيفاء (رفع الوثائق/التصحيحات)
    'interpolation_prefix' => 'application/interpolation',

    // مسار شاشة السوبر أدمن للطلبات
    'admin_prefix' => 'superadmin/applications',

    // ميدل وير السوبر أدمن (تعدلها من المشروع المستضيف)
    'admin_middleware' => ['web', 'auth'],

    // اسم الـ guard لو احتجته
    'admin_guard' => null,
    'public_layout' => 'layouts.public',
    'admin_layout' => 'layouts.admin',
    'form_builder_prefix' => 'superadmin/form-builder',
];

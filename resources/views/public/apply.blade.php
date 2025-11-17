@extends(config('application_onboarding.public_layout', 'layouts.public'))

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-2xl">
        <h1 class="text-3xl font-bold text-gray-800 text-center mb-8">@tr('Apply to Join')</h1>
        <p class="text-center text-gray-600 mb-8">@tr('Fill out the form below to submit your system for review.')</p>

        @if ($errors->any())
            <div class="p-4 mb-6 text-sm text-red-800 rounded-lg bg-red-50">
                <span class="font-medium">@tr('Please fix the errors below:')</span>
                <ul class="mt-1.5 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white p-8 rounded-lg shadow-md w-full mx-auto border">
            @if (!$hasForm)
                <div class="py-20 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white shadow mb-4">
                        <svg class="w-8 h-8 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M4 7h16M4 12h10M4 17h16" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                    </div>

                    <h2 class="text-2xl font-semibold text-gray-900 mb-2">
                        @tr('No application form defined yet')
                    </h2>

                    <p class="text-gray-600">
                        @tr('Please ask the Superadmin to define the application form first from "Application Onboarding → Form Builder".')
                    </p>
                </div>
            @else
                <form id="applicationForm" action="{{ route('application.store', $application->resubmit_token ?? null) }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf

                    @foreach ($fields as $field)
                        @if ($field->type === 'heading')
                            <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4 mt-8">
                                {{ trk("application.form.fields.$field->name.label", $field->label) }}
                            </h3>
                        @else
                            <div class="mb-4">
                                <label for="{{ $field->name }}" class="block text-gray-700 font-bold mb-2">
                                    {{ trk("application.form.fields.$field->name.label", $field->label) }}
                                    @if ($field->is_required)
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>

                                @php
                                    $mapped = $field->maps_to_column ?? null;
                                    $prefill = old($field->name);

                                    if ($prefill === null && !empty($application)) {
                                        if ($mapped !== null && isset($application->{$mapped})) {
                                            $prefill = $application->{$mapped};
                                        } elseif (isset($application->form_data[$field->name])) {
                                            $prefill = $application->form_data[$field->name];
                                        }
                                    }

                                    $prefill = is_array($prefill) ? '' : $prefill;
                                @endphp

                                @if ($field->type === 'textarea')
                                    <textarea name="{{ $field->name }}" id="{{ $field->name }}"
                                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ $prefill }}</textarea>

                                @elseif ($field->type === 'file')
                                    {{-- حقل الملفات مع إظهار أسماء الملفات المختارة --}}
                                    <label for="{{ $field->name }}"
                                           class="cursor-pointer bg-blue-50 text-blue-700 font-semibold py-2 px-4 rounded-full hover:bg-blue-100">
                                        @tr('Choose Files')
                                    </label>

                                    <input
                                        type="file"
                                        name="{{ $field->name }}[]"
                                        id="{{ $field->name }}"
                                        class="hidden file-input-ao"
                                        onchange="updateFileList('{{ $field->name }}', '{{ $field->name }}-list')"
                                        multiple>

                                    <p id="{{ $field->name }}-list" class="text-xs text-gray-500 mt-2"></p>

                                @elseif ($field->type === 'list')
                                    @php
                                        $raw = $field->options ?? [];
                                        $pairs = [];

                                        foreach ($raw as $k => $lbl) {
                                            $pairs[] = [
                                                'value' => is_int($k) ? (string) $lbl : (string) $k,
                                                'label' => (string) $lbl,
                                            ];
                                        }

                                        $pref = is_array($prefill) ? '' : (string) $prefill;
                                        $selectedValue = '';

                                        foreach ($pairs as $p) {
                                            if (strcasecmp($p['value'], $pref) === 0) {
                                                $selectedValue = $p['value'];
                                                break;
                                            }
                                        }

                                        if ($selectedValue === '' && $pref !== '') {
                                            foreach ($pairs as $p) {
                                                if (strcasecmp($p['label'], $pref) === 0) {
                                                    $selectedValue = $p['value'];
                                                    break;
                                                }
                                            }
                                        }

                                        $forceOther = false;
                                        if ($field->name === 'industry_type' && $pref !== '' && $selectedValue === '') {
                                            $forceOther = true;
                                            $selectedValue = 'Other';
                                        }

                                        $hasOther = collect($pairs)->contains(
                                            fn($p) => strcasecmp($p['value'], 'Other') === 0 ||
                                                      strcasecmp($p['label'], 'Other') === 0,
                                        );
                                        if (!$hasOther) {
                                            $pairs[] = ['value' => 'Other', 'label' => 'Other'];
                                        }

                                        $optKeyFor = function (string $label) use ($field) {
                                            return "application.form.fields.$field->name.options." .
                                                \Illuminate\Support\Str::slug($label, '_');
                                        };

                                        $__opts = [];
                                        foreach ($pairs as $p) {
                                            $__opts[$p['value']] = trk($optKeyFor($p['label']), $p['label']);
                                        }
                                    @endphp

                                    <x-ui.status-select
                                        name="{{ $field->name }}"
                                        id="{{ $field->name }}"
                                        :required="$field->is_required"
                                        :placeholder="tr('Select an option')"
                                        :value="$selectedValue"
                                        :options="$__opts" />

                                    @if ($field->name === 'industry_type')
                                        <div class="mb-4" id="industry_other_block" style="display:none">
                                            <label for="industry_type_other" class="block text-gray-700 font-bold mb-2">
                                                @tr('Please specify the industry')
                                            </label>
                                            <input type="text" name="industry_type_other" id="industry_type_other"
                                                   value="{{ old('industry_type_other', $forceOther ? $pref : '') }}"
                                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                                   placeholder="@tr('Type your industry')">
                                            @error('industry_type_other')
                                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <script>
                                            document.addEventListener('DOMContentLoaded', function () {
                                                const select = document.getElementById('{{ $field->name }}');
                                                const otherInput = document.querySelector('[name="industry_type_other"]');
                                                if (!select || !otherInput) return;

                                                const container = otherInput.closest('.mb-4') || otherInput.parentElement;

                                                const toggle = () => {
                                                    const isOther = (select.value || '').toLowerCase() === 'other';
                                                    container.style.display = isOther ? 'block' : 'none';
                                                    if (!isOther) {
                                                        otherInput.value = '';
                                                    }
                                                };

                                                @if ($forceOther)
                                                if (!otherInput.value) {
                                                    otherInput.value = @json($pref);
                                                }
                                                @endif

                                                toggle();
                                                select.addEventListener('change', toggle);
                                            });
                                        </script>
                                    @endif
                                @else
                                    <input type="{{ $field->type }}"
                                           name="{{ $field->name }}"
                                           id="{{ $field->name }}"
                                           value="{{ is_array($prefill) ? '' : $prefill }}"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                @endif

                                @error($field->name)
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    @endforeach

                    <div class="mt-8">
                        <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded">
                            @tr('Submit Application')
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @php
        $locale = app()->getLocale();
        $applicationPolicies = $applicationPolicies ?? collect();
        $seatPlans = $seatPlans ?? collect();
        $mustAcceptApplicationPolicies = true;
    @endphp
@endsection

@section('scripts')
    {{-- Alpine للمودال --}}
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        const SELECTED_LABEL = @json(tr('Selected:'));
        const NO_FILES_LABEL = @json(tr('No files selected.'));

        // تحديث نص الحقل أسفل زر اختيار الملفات - نفس الدالة المستخدمة في طابور
        function updateFileList(inputId, listId) {
            const input = document.getElementById(inputId);
            const listContainer = document.getElementById(listId);
            
            if (!input || !listContainer) return;

            if (input.files && input.files.length > 0) {
                const fileNames = Array.from(input.files).map(f => f.name);
                listContainer.textContent = SELECTED_LABEL + ' ' + fileNames.join(', ');
            } else {
                listContainer.textContent = NO_FILES_LABEL;
            }
        }

        // تهيئة عرض أسماء الملفات عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function () {
            // تحديث جميع حقول الملفات عند تحميل الصفحة
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                const listId = input.id + '-list';
                updateFileList(input.id, listId);
            });
        });

        // Robust "Other" toggler for industry_type with dynamic other-field names
        document.addEventListener('DOMContentLoaded', function () {
            const select = document.querySelector('[name="industry_type"]');
            if (!select) return;

            const otherInput =
                document.querySelector('[name="industry_type_other"]') ||
                document.querySelector('[name*="industry_type_other"]') ||
                document.querySelector('[name*="please_specify_industry_type"]');

            if (!otherInput) return;

            const container = otherInput.closest('.mb-4') || otherInput.parentElement;

            const isOtherSelected = () => {
                const val = (select.value || '').trim().toLowerCase();
                const txt = (select.options[select.selectedIndex]?.text || '').trim().toLowerCase();
                return val === 'other' || val === 'أخرى' || val === 'اخرى' ||
                    txt === 'other' || txt === 'أخرى' || txt === 'اخرى';
            };

            const toggle = () => {
                const show = isOtherSelected();
                container.style.display = show ? 'block' : 'none';
                if (!show) otherInput.value = '';
            };

            toggle();
            select.addEventListener('change', toggle);
        });
    </script>
@endsection
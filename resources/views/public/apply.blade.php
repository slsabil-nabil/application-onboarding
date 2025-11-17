@extends(config('application_onboarding.public_layout', 'layouts.public'))

@section('content')
    <div class="container mx-auto px-6 py-12 max-w-2xl">
        <h1 class="text-3xl font-bold text-gray-800 text-center mb-8">@tr('Apply to Join')</h1>
        <p class="text-center text-gray-600 mb-8">@tr('Fill out the form below to submit your business for review.')</p>

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
            <form id="applicationForm"
                  action="{{ route('application.store', $application->resubmit_token ?? null) }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="accept_policies" id="accept_policies" value="">

                @if ($errors->has('accept_policies'))
                    <div class="p-3 mb-4 text-sm rounded-lg bg-yellow-50 text-yellow-800">
                        {{ $errors->first('accept_policies') }}
                    </div>
                @endif

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
                                // القيمة السابقة من old()
                                $mapped  = $field->maps_to_column ?? null;
                                $prefill = old($field->name);

                                // إن لم توجد old() وكان هذا فتح عبر توكن إعادة التعبئة، عبّئ من السجل
                                if ($prefill === null && !empty($application)) {
                                    if ($mapped !== null && isset($application->{$mapped})) {
                                        $prefill = $application->{$mapped};
                                    } elseif (isset($application->form_data[$field->name])) {
                                        $prefill = $application->form_data[$field->name];
                                    }
                                }
                            @endphp

                            @if ($field->type === 'textarea')
                                <textarea name="{{ $field->name }}" id="{{ $field->name }}"
                                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ $prefill }}</textarea>

                            @elseif ($field->type === 'file')
                                <label for="{{ $field->name }}"
                                       class="cursor-pointer bg-blue-50 text-blue-700 font-semibold py-2 px-4 rounded-full hover:bg-blue-100">
                                    @tr('Choose Files')
                                </label>
                                <input type="file"
                                       name="{{ $field->name }}[]"
                                       id="{{ $field->name }}"
                                       class="hidden"
                                       onchange="updateFileList('{{ $field->name }}', '{{ $field->name }}-list')"
                                       multiple>
                                <p id="{{ $field->name }}-list" class="text-xs text-gray-500 mt-2"></p>

                            @elseif ($field->type === 'list')
                                @php
                                    $raw   = $field->options ?? [];
                                    $pairs = [];

                                    foreach ($raw as $k => $lbl) {
                                        $pairs[] = [
                                            'value' => is_int($k) ? (string) $lbl : (string) $k,
                                            'label' => (string) $lbl,
                                        ];
                                    }

                                    $pref          = is_array($prefill) ? '' : (string) $prefill;
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
                                        $forceOther   = true;
                                        $selectedValue = 'Other';
                                    }

                                    $hasOther = collect($pairs)->contains(
                                        fn($p) => strcasecmp($p['value'], 'Other') === 0
                                            || strcasecmp($p['label'], 'Other') === 0
                                    );
                                    if (! $hasOther) {
                                        $pairs[] = ['value' => 'Other', 'label' => 'Other'];
                                    }

                                    $optKeyFor = function (string $label) use ($field) {
                                        return "application.form.fields.$field->name.options."
                                            . \Illuminate\Support\Str::slug($label, '_');
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
                                    :options="$__opts"
                                />

                                @if ($field->name === 'industry_type')
                                    {{-- حقل يظهر فقط عند اختيار Other --}}
                                    <div class="mb-4" id="industry_other_block" style="display:none">
                                        <label for="industry_type_other" class="block text-gray-700 font-bold mb-2">
                                            @tr('Please specify the industry')
                                        </label>
                                        <input type="text"
                                               name="industry_type_other"
                                               id="industry_type_other"
                                               value="{{ old('industry_type_other', $forceOther ? $pref : '') }}"
                                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
                                               placeholder="@tr('Type your industry')">
                                        @error('industry_type_other')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <script>
                                        document.addEventListener('DOMContentLoaded', function () {
                                            const select    = document.getElementById('{{ $field->name }}');
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
        </div>
    </div>

    @php
        $locale = app()->getLocale();
        $applicationPolicies = $applicationPolicies ?? collect();
        $seatPlans           = $seatPlans ?? collect();
        $mustAcceptApplicationPolicies = true;
    @endphp

    {{-- نافذة سياسات الانضمام --}}
    <div x-data="{ open:false, accepted:false }"
         x-on:application-policies-open.window="open = true"
         x-on:application-policies-close.window="open = false"
         x-cloak
         id="applicationPoliciesModal"
         x-show="open"
         class="fixed inset-0 z-[1000] flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="open=false"></div>
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-base">
                    {{ $locale === 'ar' ? 'سياسات الانضمام' : 'Application Policies' }}
                </h3>
                <button class="text-gray-400 hover:text-gray-600" @click="open=false" aria-label="Close">&times;</button>
            </div>

            <div class="p-5 max-h-[70vh] overflow-y-auto space-y-4">
                {{-- سياسات الانضمام --}}
                @forelse($applicationPolicies as $p)
                    @php
                        $t = data_get($p->title, $locale)
                            ?? data_get($p->title, 'ar')
                            ?? data_get($p->title, 'en')
                            ?? $p->code;
                        $b = data_get($p->body, $locale)
                            ?? data_get($p->body, 'ar')
                            ?? data_get($p->body, 'en')
                            ?? '';
                    @endphp
                    <div class="rounded-xl border border-gray-200 p-3">
                        <h4 class="font-semibold text-gray-800">{{ $t }}</h4>
                        <div class="mt-2 text-sm text-gray-700 leading-6 whitespace-pre-line">
                            {!! nl2br(e($b)) !!}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">
                        {{ $locale === 'ar' ? 'لا توجد سياسات مُعلنة حالياً.' : 'No policies defined yet.' }}
                    </p>
                @endforelse

                {{-- نصوص خطط المقاعد Seat Plans --}}
                @if ($seatPlans->count())
                    <div class="mt-6 border-t border-gray-200 pt-4 space-y-3">
                        <h4 class="font-semibold text-gray-800 text-sm">
                            {{ $locale === 'ar' ? 'باقات المقاعد المتاحة' : 'Available seat plans' }}
                        </h4>

                        @foreach($seatPlans as $plan)
                            @php
                                $desc = data_get($plan->i18n, $locale)
                                    ?? data_get($plan->i18n, 'ar')
                                    ?? data_get($plan->i18n, 'en');
                            @endphp

                            <div class="rounded-xl border border-gray-200 p-3 text-xs text-gray-700 leading-5">
                                <div class="font-semibold text-gray-800">
                                    {{ $plan->name }}
                                </div>

                                <div class="mt-1 text-[11px] text-gray-600">
                                    {{ $plan->seat_count }}
                                    {{ $locale === 'ar' ? 'مقعد' : 'seats' }}
                                    •
                                    {{ $plan->period_days }}
                                    {{ $locale === 'ar' ? 'يوم' : 'days' }}
                                    •
                                    {{ $plan->price }} {{ $plan->currency }}
                                </div>

                                @if ($desc)
                                    <div class="mt-2 whitespace-pre-line">
                                        {!! nl2br(e($desc)) !!}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="px-5 pb-5">
                <label class="inline-flex items-center gap-2 text-sm text-gray-800">
                    <input id="acceptApplicationPolicies" type="checkbox"
                           class="w-4 h-4 border-gray-300 rounded" x-model="accepted">
                    <span>
                        {{ $locale === 'ar' ? 'أوافق على سياسات الانضمام' : 'I agree to the application policies' }}
                    </span>
                </label>
            </div>

            <div class="px-5 py-4 border-t flex items-center justify-end gap-3">
                <button type="button"
                        class="px-4 py-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-700"
                        @click="open=false">
                    {{ $locale === 'ar' ? 'إغلاق' : 'Close' }}
                </button>
                <button type="button"
                        id="btn-accept-application-policies"
                        class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50">
                    {{ $locale === 'ar' ? 'متابعة' : 'Continue' }}
                </button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    {{-- Alpine للمودال --}}
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        const SELECTED_LABEL = @json(tr('Selected'));

        function updateFileList(inputId, listId) {
            const input = document.getElementById(inputId);
            const listContainer = document.getElementById(listId);
            if (input.files.length > 0) {
                const fileNames = Array.from(input.files).map(f => f.name);
                listContainer.textContent = SELECTED_LABEL + ': ' + fileNames.join(', ');
            } else {
                listContainer.textContent = '';
            }
        }

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
                return val === 'other' || val === 'أخرى' || val === 'اخرى'
                    || txt === 'other' || txt === 'أخرى' || txt === 'اخرى';
            };

            const toggle = () => {
                const show = isOtherSelected();
                container.style.display = show ? 'block' : 'none';
                if (!show) otherInput.value = '';
            };

            toggle();
            select.addEventListener('change', toggle);
        });

        // منع الإرسال قبل الموافقة على السياسات
        document.addEventListener('DOMContentLoaded', function () {
            const mustAccept   = @json($mustAcceptApplicationPolicies);
            const form         = document.getElementById('applicationForm');
            const acceptHidden = document.getElementById('accept_policies');
            const modalEl      = document.getElementById('applicationPoliciesModal');
            const btnContinue  = document.getElementById('btn-accept-application-policies');
            const checkbox     = document.getElementById('acceptApplicationPolicies');

            if (!form || !acceptHidden || !modalEl || !btnContinue || !checkbox) {
                return;
            }

            function openPoliciesModal() {
                if (modalEl.__x) {
                    modalEl.__x.$data.open = true;
                } else {
                    modalEl.style.display = 'flex';
                }
                window.dispatchEvent(new CustomEvent('application-policies-open'));
            }

            function closePoliciesModal() {
                if (modalEl.__x) {
                    modalEl.__x.$data.open = false;
                } else {
                    modalEl.style.display = 'none';
                }
                window.dispatchEvent(new CustomEvent('application-policies-close'));
            }

            form.addEventListener('submit', function (e) {
                if (!mustAccept) {
                    acceptHidden.value = 'yes';
                    return;
                }

                const accepted = checkbox.checked;
                if (!accepted) {
                    e.preventDefault();
                    openPoliciesModal();
                } else {
                    acceptHidden.value = 'yes';
                }
            });

            btnContinue.addEventListener('click', function () {
                const accepted = checkbox.checked;
                if (!accepted) return;

                acceptHidden.value = 'yes';
                closePoliciesModal();
                if (form.requestSubmit) {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });

            @if ($errors->has('accept_policies') && $mustAcceptApplicationPolicies)
                openPoliciesModal();
            @endif
        });
    </script>
@endsection

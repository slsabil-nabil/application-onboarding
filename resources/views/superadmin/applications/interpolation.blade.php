@extends(config('application_onboarding.admin_layout', 'layouts.superadmin'))

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h1 class="text-xl font-bold text-slate-800 mb-3">
                @tr('Interpolation / Documents Request')
            </h1>
            <p class="text-sm text-slate-500 mb-4">
                @tr('Define which documents are required from the applicant and optionally request corrections to contact information.')
            </p>

            <div class="grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">
                        @tr('Business')
                    </div>
                    <div class="text-slate-800 font-medium">
                        {{ $application->business_name ?? '—' }}
                    </div>
                    @if ($application->industry_type)
                        <div class="text-xs text-slate-500">
                            {{ $application->industry_type }}
                        </div>
                    @endif
                </div>

                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">
                        @tr('Owner')
                    </div>
                    <div class="text-slate-800 font-medium">
                        {{ $application->owner_name ?? '—' }}
                    </div>
                    <div class="text-xs text-indigo-700">
                        {{ $application->owner_email }}
                    </div>
                    @if ($application->owner_phone)
                        <div class="text-xs text-slate-500">
                            {{ $application->owner_phone }}
                        </div>
                    @endif
                </div>

                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">
                        @tr('Current status')
                    </div>
                    <div class="text-xs">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 ring-1 ring-amber-200">
                            {{ ucfirst($application->status) }}
                        </span>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">
                        @tr('Last update')
                    </div>
                    <div class="text-xs text-slate-500">
                        {{ optional($application->updated_at)->format('Y-m-d H:i') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            @if ($errors->has('form'))
                <div class="mb-4 p-3 rounded-xl bg-rose-50 text-rose-700 text-sm">
                    {{ $errors->first('form') }}
                </div>
            @endif

            <form method="POST"
                  action="{{ route('superadmin.applications.submit-documents', $application) }}"
                  class="space-y-6">
                @csrf

                {{-- الوثائق المطلوبة --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-sm font-semibold text-slate-800">
                            @tr('Required documents')
                        </h2>
                        <button type="button"
                                id="add-doc-row"
                                class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium
                                       bg-slate-100 text-slate-700 hover:bg-slate-200">
                            + @tr('Add document')
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mb-3">
                        @tr('Describe each document you need the applicant to upload (e.g. commercial register, tax card, ID...).')
                    </p>

                    @php
                        $oldDocs = old('docs', (array) ($application->interpolation_required_docs ?? []));
                        if (count($oldDocs) === 0) {
                            $oldDocs = [''];
                        }
                    @endphp

                    <div id="docs-wrapper" class="space-y-2">
                        @foreach ($oldDocs as $i => $doc)
                            <div class="flex items-center gap-2 doc-row">
                                <input type="text"
                                       name="docs[{{ $i }}]"
                                       value="{{ $doc }}"
                                       class="flex-1 border rounded-lg px-3 py-2 text-sm"
                                       placeholder="@tr('Document description (e.g. Commercial Registration)')">
                                <button type="button"
                                        class="remove-doc px-2 py-1 text-xs rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100">
                                    @tr('Remove')
                                </button>
                            </div>
                        @endforeach
                    </div>

                    @error('docs.*')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ملاحظة للسجل --}}
                <div>
                    <h2 class="text-sm font-semibold text-slate-800 mb-2">
                        @tr('Message / Note to applicant')
                    </h2>
                    <textarea name="interpolation_note"
                              rows="4"
                              class="w-full border rounded-lg px-3 py-2 text-sm text-slate-800"
                              placeholder="@tr('Explain why you are requesting these documents or what needs to be corrected.')">{{ old('interpolation_note', $application->interpolation_note) }}</textarea>
                    @error('interpolation_note')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- تصحيحات بيانات الاتصال --}}
                <div>
                    <h2 class="text-sm font-semibold text-slate-800 mb-2">
                        @tr('Request contact corrections (optional)')
                    </h2>
                    @php
                        $oldCorrections = old('request_corrections', (array) ($application->interpolation_contact_corrections ?? []));
                    @endphp

                    <div class="space-y-2 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox"
                                   name="request_corrections[]"
                                   value="owner_email"
                                   class="rounded border-slate-300"
                                   {{ in_array('owner_email', $oldCorrections, true) ? 'checked' : '' }}>
                            <span>@tr('Ask applicant to correct email address')</span>
                        </label>

                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox"
                                   name="request_corrections[]"
                                   value="owner_phone"
                                   class="rounded border-slate-300"
                                   {{ in_array('owner_phone', $oldCorrections, true) ? 'checked' : '' }}>
                            <span>@tr('Ask applicant to correct phone number')</span>
                        </label>
                    </div>

                    @error('request_corrections.*')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror

                    <p class="mt-2 text-xs text-slate-500">
                        @tr('You can leave this empty if you only need documents without changing contact information.')
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <a href="{{ route('superadmin.applications.index', ['status' => 'pending']) }}"
                       class="px-4 py-2 rounded-xl text-sm bg-slate-100 text-slate-700 hover:bg-slate-200">
                        @tr('Cancel')
                    </a>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                        @tr('Send documents request')
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wrapper = document.getElementById('docs-wrapper');
            const addBtn  = document.getElementById('add-doc-row');

            if (!wrapper || !addBtn) return;

            function refreshRemoveButtons() {
                wrapper.querySelectorAll('.remove-doc').forEach(btn => {
                    btn.onclick = function () {
                        const row = btn.closest('.doc-row');
                        if (!row) return;
                        // لا تحذف آخر صف
                        if (wrapper.querySelectorAll('.doc-row').length > 1) {
                            row.remove();
                        } else {
                            row.querySelector('input')?.value = '';
                        }
                    };
                });
            }

            addBtn.addEventListener('click', function () {
                const index = wrapper.querySelectorAll('.doc-row').length;
                const div   = document.createElement('div');
                div.className = 'flex items-center gap-2 doc-row';
                div.innerHTML = `
                    <input type="text"
                           name="docs[${index}]"
                           class="flex-1 border rounded-lg px-3 py-2 text-sm"
                           placeholder="@tr('Document description (e.g. Commercial Registration)')">
                    <button type="button"
                            class="remove-doc px-2 py-1 text-xs rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100">
                        @tr('Remove')
                    </button>
                `;
                wrapper.appendChild(div);
                refreshRemoveButtons();
            });

            refreshRemoveButtons();
        });
    </script>
@endsection

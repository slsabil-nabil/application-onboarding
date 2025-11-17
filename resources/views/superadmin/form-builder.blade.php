@extends(config('application_onboarding.admin_layout', 'layouts.admin'))

@section('content')
<div class="p-4 sm:p-6 w-full max-w-none">
    @php $rtl = app()->isLocale('ar'); @endphp

    <h1 class="text-3xl font-bold text-gray-800 mb-6">@tr('Application Form Builder')</h1>

    @if ($errors->any())
        <div class="p-4 mb-6 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
            <span class="font-medium">@tr('Please fix the following errors:')</span>
            <ul class="mt-1.5 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form to Add New Field --}}
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-xl font-semibold mb-4">@tr('Add New Field')</h2>

        <form action="{{ route('superadmin.form-builder.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <input id="fld-label" name="label"
                       placeholder="@tr('Field Label (e.g., Business Name)')"
                       class="border p-2 rounded w-full min-w-0" required>

                <div class="relative">
                    <input id="fld-name" name="name"
                           placeholder="@tr('Field Name (auto-generates)')"
                           class="border p-2 rounded pr-20 w-full min-w-0" readonly>
                </div>

                <x-ui.status-select
                    name="maps_to_column"
                    id="fld-maps"
                    :options="array_combine($mappableColumns, $mappableColumns)"
                    :placeholder="'(' . tr('Do not map') . ')'"
                />

                <x-ui.status-select
                    name="type"
                    id="fieldType"
                    :options="[
                        'text'     => tr('Text'),
                        'email'    => tr('Email'),
                        'textarea' => tr('Text Area'),
                        'tel'      => tr('Phone Number'),
                        'file'     => tr('File'),
                        'list'     => tr('List'),
                        'heading'  => tr('Heading'),
                    ]"
                />
            </div>

            <div id="optionsContainer" class="mt-4" style="display:none;">
                <label for="options" class="block font-medium text-gray-700 mb-1">@tr('Options')</label>
                <input type="text" name="options" id="options"
                       placeholder="@tr('Enter options, separated by a comma (e.g., Option1,Option2)')"
                       class="border p-2 rounded w-full">
                <p class="text-xs text-gray-500 mt-1">@tr("Only required if Type is 'List'.")</p>
            </div>

            <div class="mt-4">
                <label class="font-medium text-gray-700">
                    <input type="checkbox" name="is_required" value="1" checked>
                    @tr('Is Required?')
                </label>
            </div>

            <div class="mt-4">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded">
                    @tr('Add Field')
                </button>
            </div>
        </form>
    </div>

    {{-- List of Current Fields --}}
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4">@tr('Current Form Fields')</h2>
        <ul id="sortable-fields" class="divide-y divide-gray-200">
            @forelse($fields as $field)
                <li class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 py-3"
                    data-id="{{ $field->id }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-400 {{ $rtl ? 'ml-3' : 'mr-3' }} shrink-0 cursor-grab"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <div>
                            <span class="font-medium text-gray-800">{{ $field->label }}</span>
                            <span class="text-sm text-gray-500 ml-2">({{ $field->type }})</span>
                            @if($field->maps_to_column)
                                <span
                                    class="text-xs text-purple-600 bg-purple-100 rounded-full px-2 py-0.5 ml-2 break-words">
                                    @tr('Maps to:') {{ $field->maps_to_column }}
                                </span>
                            @endif
                        </div>
                        @if($field->is_required && $field->type !== 'heading')
                            <span class="text-red-500 ml-1">*</span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-2 sm:mt-0" @if($rtl) dir="ltr" @endif>
                        <a href="{{ route('superadmin.form-builder.edit', $field) }}"
                           class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-md hover:bg-blue-200">
                            @tr('Edit')
                        </a>
                        <button type="button"
                                data-url="{{ route('superadmin.form-builder.destroy', $field) }}"
                                class="delete-field-btn bg-red-100 text-red-800 text-sm font-medium px-3 py-1 rounded-md hover:bg-red-200">
                            @tr('Delete')
                        </button>
                    </div>
                </li>
            @empty
                <li class="text-gray-500">@tr('No fields defined yet.')</li>
            @endforelse
        </ul>
    </div>
</div>

{{-- Modal --}}
<div id="deleteConfirmationModal"
     class="fixed inset-0 bg-gray-600 bg-opacity-50 h-full w-full flex items-center justify-center hidden z-50">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md mx-auto">
        <div class="text-center">
            <h3 class="text-2xl font-bold text-gray-900">@tr('Confirm Deletion')</h3>
            <p class="text-gray-600 mt-2 mb-8">
                @tr('Are you sure you want to delete this form field? This action cannot be undone.')
            </p>

            <form id="deleteForm" action="" method="POST">
                @csrf
                @method('DELETE')
                <div class="flex justify-center gap-4">
                    <button type="submit"
                            class="py-2 px-4 bg-red-600 text-white rounded hover:bg-red-700 font-bold">
                        @tr('Yes, delete field')
                    </button>
                    <button type="button" id="cancelDeleteBtn"
                            class="py-2 px-4 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 font-medium">
                        @tr('Cancel')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fieldTypeList     = document.getElementById('fieldType');
    const maps              = document.getElementById('fld-maps');
    const columnMeta        = @json($columnMeta ?? []);
    const optionsContainer  = document.getElementById('optionsContainer');

    if (fieldTypeList) {
        fieldTypeList.addEventListener('change', function () {
            optionsContainer.style.display = (this.value === 'list') ? 'block' : 'none';
        });
    }

    function allowedTypesFor(dbType, colName) {
        const nm = (colName || '').toLowerCase();

        if (nm === 'business_name') return ['text'];
        if (nm === 'owner_name') return ['text'];
        if (nm.includes('email')) return ['email', 'text'];
        if (nm === 'industry_type' || nm.includes('industry')) return ['list'];
        if (nm.includes('phone') || nm.includes('mobile') || nm.includes('tel')) return ['tel', 'text'];
        if (nm.includes('file') || nm.includes('path')) return ['file', 'text'];
        if (nm.includes('desc') || nm.includes('notes') || nm.includes('address')) return ['textarea', 'text'];

        switch (dbType) {
            case 'integer':
            case 'bigint':
            case 'smallint':
            case 'decimal':
            case 'float':
            case 'double':
                return ['text'];
            case 'boolean':
                return ['list'];
            case 'enum':
                return ['list', 'text'];
            case 'text':
                return ['textarea', 'text'];
            case 'json':
            case 'longtext':
                return ['text','textarea','list','file'];
            case 'date':
            case 'datetime':
            case 'timestamp':
            case 'time':
                return ['text'];
            case 'string':
            case 'varchar':
            case 'char':
                return ['text','email','tel','file'];
            default:
                return ['text','email','tel','textarea','list','file','heading'];
        }
    }

    maps?.addEventListener('change', () => {
        if (!fieldTypeList) return;

        Array.from(fieldTypeList.options).forEach(o => o.disabled = false);

        const col     = maps.value;
        if (!col) return;

        const dbType  = columnMeta[col] || 'string';
        const allowed = allowedTypesFor(dbType, col);

        Array.from(fieldTypeList.options).forEach(o => {
            if (!allowed.includes(o.value)) o.disabled = true;
        });

        if (!allowed.includes(fieldTypeList.value)) {
            fieldTypeList.value = allowed[0] || 'text';
            optionsContainer.style.display = (fieldTypeList.value === 'list') ? 'block' : 'none';
        }
    });

    // Sortable
    const sortableList = document.getElementById('sortable-fields');
    if (sortableList) {
        new Sortable(sortableList, {
            animation: 150,
            ghostClass: 'bg-blue-50',
            onEnd: function () {
                const order = Array.from(sortableList.children).map(el => el.dataset.id);
                fetch('{{ route("superadmin.form-builder.reorder") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({order}),
                });
            }
        });
    }

    // Delete modal
    const deleteModal    = document.getElementById('deleteConfirmationModal');
    const deleteForm     = document.getElementById('deleteForm');
    const cancelDeleteBtn= document.getElementById('cancelDeleteBtn');
    const deleteButtons  = document.querySelectorAll('.delete-field-btn');

    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const deleteUrl = this.dataset.url;
            deleteForm.action = deleteUrl;
            deleteModal.classList.remove('hidden');
        });
    });

    cancelDeleteBtn?.addEventListener('click', () => {
        deleteModal.classList.add('hidden');
    });

    deleteModal?.addEventListener('click', (e) => {
        if (e.target === deleteModal) deleteModal.classList.add('hidden');
    });

    // auto-generate name from label
    const lbl       = document.getElementById('fld-label');
    const nameInput = document.getElementById('fld-name');

    function slugifyToSnake(s) {
        const AR_DIGITS = {'٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9'};
        const AR_MAP = {
            'أ':'a','إ':'i','آ':'a','ا':'a','ب':'b','ت':'t','ث':'th','ج':'j','ح':'h','خ':'kh',
            'د':'d','ذ':'dh','ر':'r','ز':'z','س':'s','ش':'sh','ص':'s','ض':'d','ط':'t','ظ':'z',
            'ع':'a','غ':'gh','ف':'f','ق':'q','ك':'k','ل':'l','م':'m','ن':'n','ه':'h','و':'w','ؤ':'w',
            'ي':'y','ئ':'y','ى':'a','ة':'h','ﻻ':'la','لا':'la'
        };
        return s
            .replace(/[\u0610-\u061A\u064B-\u065F\u0670\u06D6-\u06ED\u0640]/g,'')
            .replace(/./gu, ch => (AR_DIGITS[ch] ?? AR_MAP[ch] ?? ch))
            .trim()
            .replace(/[^\p{L}\p{N}]+/gu, '_')
            .replace(/^_+|_+$/g,'')
            .toLowerCase();
    }

    lbl?.addEventListener('input', () => {
        if (!lbl.value) return;
        nameInput.value = slugifyToSnake(lbl.value);
    });
});
</script>
@endpush

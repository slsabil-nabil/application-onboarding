@extends(config('application_onboarding.admin_layout', 'layouts.admin'))

@section('content')
<div class="p-4 sm:p-6 w-full max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        @tr('Edit Form Field')
    </h1>

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

    @php
        $optionsString = $field->options
            ? implode(',', array_values($field->options))
            : '';
    @endphp

    <div class="bg-white p-6 rounded-lg shadow-md">
        <form action="{{ route('superadmin.form-builder.update', $field) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Label --}}
            <div class="mb-4">
                <label for="label" class="block font-medium text-gray-700 mb-1">
                    @tr('Field Label')
                </label>
                <input id="label"
                       name="label"
                       value="{{ old('label', $field->label) }}"
                       class="border p-2 rounded w-full"
                       required>
            </div>

            {{-- Name --}}
            <div class="mb-4">
                <label for="name" class="block font-medium text-gray-700 mb-1">
                    @tr('Field Name')
                </label>
                <input id="name"
                       name="name"
                       value="{{ old('name', $field->name) }}"
                       class="border p-2 rounded w-full"
                       @if($field->type === 'heading') readonly @endif>
                <p class="text-xs text-gray-500 mt-1">
                    @tr('Used as the key in the submitted data.')
                </p>
            </div>

            {{-- Type --}}
            <div class="mb-4">
                <label for="fieldType" class="block font-medium text-gray-700 mb-1">
                    @tr('Field Type')
                </label>
                <x-ui.status-select
                    name="type"
                    id="fieldType"
                    :value="old('type', $field->type)"
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

            {{-- Maps to column --}}
            <div class="mb-4">
                <label for="fld-maps" class="block font-medium text-gray-700 mb-1">
                    @tr('Maps to Column (optional)')
                </label>
                <x-ui.status-select
                    name="maps_to_column"
                    id="fld-maps"
                    :value="old('maps_to_column', $field->maps_to_column)"
                    :options="array_combine($mappableColumns, $mappableColumns)"
                    :placeholder="'(' . tr('Do not map') . ')'"
                />
                <p class="text-xs text-gray-500 mt-1">
                    @tr('If selected, this field will be saved directly in the given column.')
                </p>
            </div>

            {{-- Options --}}
            <div class="mb-4" id="optionsContainer" style="display: none;">
                <label for="options" class="block font-medium text-gray-700 mb-1">
                    @tr('Options (for List type)')
                </label>
                <input type="text"
                       name="options"
                       id="options"
                       value="{{ old('options', $optionsString) }}"
                       placeholder="@tr('Enter options, separated by a comma (e.g., Option1,Option2)')"
                       class="border p-2 rounded w-full">
                <p class="text-xs text-gray-500 mt-1">
                    @tr("Only required if Type is 'List'.")
                </p>
            </div>

            {{-- Required --}}
            <div class="mb-6">
                <label class="font-medium text-gray-700">
                    <input type="checkbox"
                           name="is_required"
                           value="1"
                           {{ old('is_required', $field->is_required) ? 'checked' : '' }}>
                    @tr('Is Required?')
                </label>
            </div>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('superadmin.form-builder.index') }}"
                   class="px-4 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                    @tr('Back')
                </a>

                <button type="submit"
                        class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white font-bold">
                    @tr('Save Changes')
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fieldTypeList   = document.getElementById('fieldType');
        const optionsContainer = document.getElementById('optionsContainer');

        if (fieldTypeList && optionsContainer) {
            const toggleOptions = () => {
                optionsContainer.style.display = (fieldTypeList.value === 'list') ? 'block' : 'none';
            };
            toggleOptions();
            fieldTypeList.addEventListener('change', toggleOptions);
        }
    });
</script>
@endpush

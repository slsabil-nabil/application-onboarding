@extends(config('application_onboarding.public_layout', 'layouts.public'))

@section('content')
    <div class="container mx-auto px-6 py-16">
        <div class="bg-white rounded-lg shadow p-6 w-full max-w-xl mx-auto">
            <h1 class="text-2xl font-bold mb-4 text-center">@tr('Application Review - Request')</h1>

            @if ($errors->has('form'))
                <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm">
                    {{ $errors->first('form') }}
                </div>
            @endif

            @if ($errors->has('link'))
                <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm">
                    {{ $errors->first('link') }}
                </div>
            @endif

            <form method="POST"
                  action="{{ route('interpolation.submit', $token) }}"
                  enctype="multipart/form-data"
                  class="space-y-4">
                @csrf

                @foreach ($requiredDocs ?? [] as $i => $docLabel)
                    <div>
                        <label class="block font-medium mb-1">
                            @tr('Document') {{ $i + 1 }} — {{ $docLabel }}
                        </label>
                        <input type="file" name="files[{{ $i }}]" class="block w-full border rounded p-2" required>
                        @error("files.$i")
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                @if (!empty($note) || !empty($corrections))
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        @if ($note)
                            <p class="font-medium mb-2">{{ $note }}</p>
                        @endif

                        @foreach ((array) $corrections as $c)
                            @php
                                $isEmail = $c === 'owner_email';
                                $lbl     = $isEmail ? __('Email') : __('Phone');
                                $name    = $isEmail ? 'fix_owner_email' : 'fix_owner_phone';
                                $type    = $isEmail ? 'email' : 'text';
                                $default = $isEmail
                                            ? ($application->owner_email ?? '')
                                            : ($application->owner_phone ?? '');
                            @endphp

                            <label class="block font-medium mb-1">{{ $lbl }}</label>
                            <input type="{{ $type }}"
                                   name="{{ $name }}"
                                   value="{{ old($name, $default) }}"
                                   class="block w-full border rounded p-2 mb-3"
                                   required>
                            @error($name)
                                <p class="text-red-600 text-sm -mt-2 mb-2">{{ $message }}</p>
                            @enderror
                        @endforeach
                    </div>
                @endif

                <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded">
                    @tr('Send')
                </button>
            </form>
        </div>
    </div>
@endsection

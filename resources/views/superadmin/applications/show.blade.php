@extends(config('application_onboarding.admin_layout', 'layouts.superadmin'))

@section('content')

    <div class="p-4 sm:p-6 max-w-5xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 sm:gap-3 mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 break-words">@tr('Application Details')</h1>
            <a href="{{ route('superadmin.applications.index', ['status' => $application->status]) }}"
                class="text-blue-600 hover:underline w-full sm:w-auto text-center">
                &larr; @tr('Back to Applications')
            </a>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md w-full">
            {{-- Details and Documents sections remain the same... --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 border-b pb-4 sm:pb-6 mb-4 sm:mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">@tr('Business Details')</h3>
                    <p><strong class="font-medium text-gray-600">@tr('Business Name'):</strong>
                        {{ $application->business_name }}</p>

                    @php
                        $val = (string) $application->industry_type;
                        $opts = $industryOptions ?? [];
                        $label = $val;

                        // إن كانت الخيارات ترابطية [key=>label] استخدم الـ label، وإلا إن كانت مصفوفة عادية فكفايـة القيمة نفسها
                        $isAssoc = array_keys($opts) !== range(0, count($opts) - 1);
                        if ($isAssoc && isset($opts[$val])) {
                            $label = $opts[$val];
                        } elseif (!$isAssoc && in_array($val, $opts, true)) {
                            $label = $val;
                        }
                    @endphp
                    <p><strong class="font-medium text-gray-600">@tr('Industry'):</strong> {{ $label }}</p>


                    <p><strong class="font-medium text-gray-600">@tr('Submitted On'):</strong>
                        {{ $application->created_at->format('Y-m-d') }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">@tr('Owner Details')</h3>
                    <p><strong class="font-medium text-gray-600">@tr("Owner's Name"):</strong> {{ $application->owner_name }}
                    </p>
                    <p>
                        <strong class="font-medium text-gray-600">@tr('Email'):</strong>
                        <a href="mailto:{{ $application->owner_email }}"
                            class="text-blue-600">{{ $application->owner_email }}</a>
                    </p>
                    <p><strong class="font-medium text-gray-600">@tr('Phone'):</strong>
                        {{ $application->owner_phone }}</p>
                </div>
            </div>
            @php
                // نجمع الملفات من الأعمدة المباشرة
                $licenses = collect($application->licenses_paths ?? []);
                $supporting = collect($application->supporting_documents_paths ?? []);

                // لو كانت الحقول غير مربوطة وحُفظت في form_data، التقط أي مصفوفات مسارات هناك
                $formFiles = collect($application->form_data ?? [])->filter(
                    fn($v) => is_array($v) && isset($v[0]) && is_string($v[0]),
                );

                if ($licenses->isEmpty()) {
                    $licenses = $formFiles
                        ->filter(
                            fn($v, $k) => \Illuminate\Support\Str::contains(strtolower($k), ['license', 'licence']),
                        )
                        ->flatten();
                }

                if ($supporting->isEmpty()) {
                    $supporting = $formFiles
                        ->filter(
                            fn($v, $k) => \Illuminate\Support\Str::contains(strtolower($k), [
                                'support',
                                'document',
                                'doc',
                                'attachment',
                                'file',
                            ]),
                        )
                        ->flatten();
                }

                $licenses = $licenses->filter(fn($p) => is_string($p) && $p !== '')->values();
                $supporting = $supporting->filter(fn($p) => is_string($p) && $p !== '')->values();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">@tr('Business License(s)')</h3>
                    @forelse($licenses as $path)
                        <a href="{{ Storage::disk('public')->url($path) }}" target="_blank"
                            class="block text-blue-600 hover:underline mb-1 break-all">
                            📄 {{ basename($path) }}
                        </a>
                    @empty
                        <p class="text-gray-500">@tr('No licenses were uploaded.')</p>
                    @endforelse
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">@tr('Supporting Documents')</h3>
                    @forelse($supporting as $path)
                        <a href="{{ Storage::disk('public')->url($path) }}" target="_blank"
                            class="block text-blue-600 hover:underline mb-1 break-all">
                            📄 {{ basename($path) }}
                        </a>
                    @empty
                        <p class="text-gray-500">@tr('No supporting documents were uploaded.')</p>
                    @endforelse
                </div>
            </div>
            {{-- Interpolation uploads --}}
            @if (!empty($application->interpolation_uploaded_files))
                <div class="border-t mt-6 pt-6 mb-6 sm:mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">@tr('Interpolation Uploads')</h3>
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                        <ul class="space-y-2 text-gray-700"> {{-- أزلنا list-disc حتى لا تتكرر النقاط --}}
                            @foreach ($application->interpolation_uploaded_files ?? [] as $f)
                                <li class="flex items-center gap-2">
                                    <span class="text-gray-400">•</span>
                                    <span class="font-medium">{{ $f['label'] ?? __('Document') }}</span>
                                    <a class="text-blue-600 hover:underline ml-2"
                                        href="{{ Storage::disk('public')->url($f['path']) }}" target="_blank">
                                        @tr('View file')
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @if ($application->status === 'rejected' && $application->rejection_reason)
                <div class="mb-8 p-4 bg-red-50 text-red-700 rounded-lg">
                    <h3 class="font-bold mb-1">@tr('Reason for Rejection:')</h3>
                    <p>{{ $application->rejection_reason }}</p>
                </div>
            @endif

            @if ($application->status === 'pending')
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-4">
                    <form method="POST" action="{{ route('superadmin.applications.approve', $application) }}">
                        @csrf
                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded w-full sm:w-auto">
                            @tr('Approve')
                        </button>
                    </form>

                    <button type="button" onclick="openDeclineModal()"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded w-full sm:w-auto">
                        @tr('Decline')
                    </button>

                    <a href="{{ route('superadmin.applications.interpolation.show', $application) }}"
                        class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-6 rounded transition-colors duration-200 w-full sm:w-auto text-center">
                        @tr('Application Review')
                    </a>
                </div>

                <!-- Modal: رفض مع/بدون سبب -->
                <div id="declineModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
                    <div class="w-full max-w-lg bg-white rounded-xl p-6">
                        <h3 class="text-lg font-semibold mb-2">@tr('Decline Application')</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            @tr('Do you want to send a short reason to the applicant? (optional)')
                        </p>

                        <form id="declineForm" method="POST"
                            action="{{ route('superadmin.applications.decline', $application) }}">
                            @csrf
                            <input type="hidden" name="include_reason" id="include_reason" value="0">

                            <label class="block text-sm font-medium mb-1">@tr('Reason (max 120 chars)')</label>
                            <textarea name="rejection_reason" id="rejection_reason" maxlength="120" class="w-full border rounded p-2 h-28"
                                placeholder="@tr('e.g., does not meet our registration criteria')"></textarea>
                            <p class="text-gray-500 text-xs mt-1">
                                @tr('For document requests use “Application Review”, not this reason box.')
                            </p>
                            <div class="mt-5 flex gap-2">
                                <button type="submit" onclick="document.getElementById('include_reason').value='1'"
                                    class="px-4 py-2 bg-red-600 text-white rounded">
                                    @tr('Decline and send reason')
                                </button>

                                <button type="submit" onclick="document.getElementById('include_reason').value='0'"
                                    class="px-4 py-2 bg-gray-800 text-white rounded">
                                    @tr('Decline without reason')
                                </button>

                                <button type="button" onclick="closeDeclineModal()"
                                    class="ml-auto px-4 py-2 border rounded">
                                    @tr('Cancel')
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    function openDeclineModal() {
                        const m = document.getElementById('declineModal');
                        m.classList.remove('hidden');
                        m.classList.add('flex');
                    }

                    function closeDeclineModal() {
                        const m = document.getElementById('declineModal');
                        m.classList.add('hidden');
                        m.classList.remove('flex');
                    }
                </script>
            @endif
        </div>
    </div>

    <script>
        // Keep existing behavior. Strings in this script are not user-visible.
        // If you later surface alerts or labels, pass translated strings from Blade as in previous file.
        const approveBtn = document.getElementById('approveBtn');
        const declineBtn = document.getElementById('declineBtn');
        const actionInput = document.getElementById('actionInput');
        const responseForm = document.getElementById('responseForm');
        const rejectionContainer = document.getElementById('rejectionContainer');

        if (approveBtn) {
            approveBtn.addEventListener('click', function() {
                actionInput.value = 'approve';
                responseForm.submit();
            });
        }

        if (declineBtn) {
            declineBtn.addEventListener('click', function() {
                if (rejectionContainer.style.display === 'block') {
                    actionInput.value = 'decline';
                    responseForm.submit();
                } else {
                    rejectionContainer.style.display = 'block';
                }
            });
        }
    </script>
@endsection

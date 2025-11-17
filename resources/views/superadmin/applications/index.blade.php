@extends(config('application_onboarding.admin_layout', 'layouts.admin'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    @tr('Onboarding Applications')
                </h1>
                <p class="text-sm text-slate-500">
                    @tr('Review, accept, reject, or request additional documents for onboarding applications.')
                </p>
            </div>

            <div class="inline-flex rounded-xl border border-slate-200 bg-white overflow-hidden text-xs sm:text-sm">
                @php
                    $statuses = [
                        'pending' => tr('Pending'),
                        'interpolation' => tr('Interpolation'),
                        'accepted' => tr('Accepted'),
                        'rejected' => tr('Rejected'),
                    ];
                @endphp

                @foreach ($statuses as $code => $label)
                    <a href="{{ route('superadmin.applications.index', ['status' => $code]) }}"
                        class="px-3 py-1.5 sm:px-4 sm:py-2 border-slate-200
                              {{ $currentStatus === $code
                                  ? 'bg-indigo-600 text-white font-semibold'
                                  : 'bg-white text-slate-700 hover:bg-slate-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide"> @tr('Number')</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            @tr('Business')
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            @tr('Owner')
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            @tr('Status')
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            @tr('Interpolation')
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            @tr('Submitted at')
                        </th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            @tr('Actions')
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($applications as $app)
                        <tr>
                            <td class="px-4 py-3 text-slate-700 text-xs">
                                {{ $app->id }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-slate-800">
                                    {{ $app->business_name ?? '—' }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $app->industry_type ?? '' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-slate-800">
                                    {{ $app->owner_name ?? '—' }}
                                </div>
                                <div class="text-xs text-indigo-700">
                                    {{ $app->owner_email }}
                                </div>
                                @if ($app->owner_phone)
                                    <div class="text-xs text-slate-500">
                                        {{ $app->owner_phone }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-amber-50 text-amber-800 ring-amber-200',
                                        'interpolation' => 'bg-sky-50 text-sky-800 ring-sky-200',
                                        'accepted' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                                        'rejected' => 'bg-rose-50 text-rose-800 ring-rose-200',
                                    ];
                                    $stColor =
                                        $statusColors[$app->status] ?? 'bg-slate-50 text-slate-700 ring-slate-200';
                                @endphp
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 {{ $stColor }}">
                                    {{ ucfirst($app->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if ($app->interpolation === 'pending')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 ring-1 ring-amber-200">
                                        @tr('Waiting for documents')
                                    </span>
                                @elseif ($app->interpolation === 'completed')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200">
                                        @tr('Completed')
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ optional($app->created_at)->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @if (in_array($app->status, ['pending', 'interpolation']))
                                        <a href="{{ route('superadmin.applications.interpolation.show', $app) }}"
                                            class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium
              bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                                            @tr('Request documents')
                                        </a>
                                    @endif
                                    {{-- يمكن لاحقاً إضافة أزرار قبول/رفض هنا --}}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                @tr('No applications found for this status.')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

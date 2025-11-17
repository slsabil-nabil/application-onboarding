@extends(config('application_onboarding.public_layout', 'layouts.public'))

@section('content')
    <div class="container mx-auto px-6 py-20 text-center">
        <div class="bg-white p-10 rounded-lg shadow-md max-w-lg mx-auto">
            <h1 class="text-3xl font-bold text-green-600 mb-4">✅ @tr('Application Submitted!')</h1>
            <p class="text-gray-700 text-lg">
                @tr('Thank you for applying. We have received your application and will review it shortly. You will be notified of our decision via email.')
            </p>
            <a href="{{ url('/') }}"
               class="inline-block mt-8 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                @tr('Return to Homepage')
            </a>
        </div>
    </div>
@endsection

@extends(config('application_onboarding.public_layout', 'layouts.public'))

@section('content')
    <div class="container mx-auto px-6 py-20 text-center">
        <div class="bg-white p-10 rounded-lg shadow-md max-w-lg mx-auto">
            <h1 class="text-3xl font-bold mb-4" style="color: green;">✅ @tr('Documents Submitted!')</h1>
            <p class="text-gray-700 text-lg mb-6">
                @tr('Thank you. Your documents were received. Your application has been returned to review.')
            </p>
            <a href="{{ url('/') }}"
               class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                @tr('Return to Homepage')
            </a>
        </div>
    </div>
@endsection

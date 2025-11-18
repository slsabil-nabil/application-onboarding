<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@tr('Request for Missing Documents')</title>
</head>

<body>
    <h2>@tr('Dear Client,')</h2>

    <p>@tr('We are missing the following documents from your application:')</p>

    <div>
        @foreach ($docs ?? [] as $i => $doc)
            <p><strong>@tr('Document') {{ $i + 1 }}:</strong> {{ $doc }}</p>
        @endforeach
    </div>

    @if (!empty($note) || !empty($corrections))
        <hr>
        <p><strong>@tr('Additionally, please correct:')</strong></p>
        <ul>
            @foreach ($corrections as $c)
                <li>
                    {{ $c === 'owner_email' ? __('Email') : __('Phone') }}
                </li>
            @endforeach
        </ul>

        @if (!empty($note))
            <p><strong>@tr('Note:')</strong> {{ $note }}</p>
        @endif
    @endif

    <p>@tr('Please upload these documents at your earliest convenience.')</p>

    <p>
        @tr('To submit the missing documents, please visit the following link:')
        <a href="{{ $applicationFormLink }}" target="_blank">
            @tr('Application Form')
        </a>
    </p>

    <p>@tr('Thank you for your cooperation!')</p>
</body>

</html>

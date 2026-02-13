<div>
    <h1>User Documents</h1>
    <ul>
        @foreach ($documents as $document)
            <li>
                <a href="{{ asset($document->path) }}" target="_blank">{{ $document->name }}</a>
            </li>
        @endforeach
    </ul>
</div>

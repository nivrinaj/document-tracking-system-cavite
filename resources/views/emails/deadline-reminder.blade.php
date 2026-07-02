<x-mail::message>
# Deadline Reminder

Hi {{ $user->first_name ?: $user->name }},

You are currently holding the following document(s) with an approaching or passed deadline:

<x-mail::table>
| Tracking Code | Title | Deadline | Status |
| :--- | :--- | :--- | :--- |
@foreach($documents as $doc)
@php $highlight = $doc->deadlineHighlight(); @endphp
| {{ $doc->tracking_code }} | {{ \Illuminate\Support\Str::limit($doc->title, 40) }} | {{ optional($doc->deadline)->format('M j, Y') }} | {{ $highlight['label'] ?? '' }} |
@endforeach
</x-mail::table>

<x-mail::button :url="route('documents.index')">
View My Documents
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

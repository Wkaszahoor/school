<x-mail::message>
# Subject Selection Update

A student has updated their subject selections.

**Student Details:**
- **Name:** {{ $studentName }}
- **Class:** {{ $studentClass }}
- **Stream:** {{ $stream }}

**Selected Subjects:**

<x-mail::table>
| Subject | Group | Type |
|---------|-------|------|
@foreach($subjects as $subject)
| {{ $subject['name'] }} | {{ $subject['group'] }} | {{ ucfirst($subject['type']) }} |
@endforeach
</x-mail::table>

<x-mail::button :url="$selectionUrl">
View Full Selection Details
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

<x-mail::message>
# System Warning

The portal detected a system warning for **{{ str($eventType)->replace(['_', '.'], ' ')->title() }}**.

@foreach ($warnings as $warning)
- {{ $warning }}
@endforeach

@if (filled($detailsUrl))
<x-mail::button :url="$detailsUrl">
Open System Health
</x-mail::button>
@endif

Use the portal to review the full health details before taking action.
</x-mail::message>

<x-mail::message>
# Scan Failed

The {{ $scheduled ? 'scheduled' : 'manual' }} scan for **{{ $project->project_name }}** failed.

- Branch: `{{ $history->branch_name }}`
- Scan type: `{{ strtoupper($history->scan_type) }}`
- Failure category: `{{ strtoupper($history->failure_category ?? 'unknown') }}`
- Completed at: `{{ optional($history->completed_at)->format('d M Y H:i') ?? 'N/A' }}`

<x-mail::button :url="$portalUrl">
Review Scan History
</x-mail::button>

@if (filled($sonarUrl))
<x-mail::button :url="$sonarUrl" color="success">
Open SonarQube
</x-mail::button>
@endif

Sensitive logs are intentionally excluded from email. Use the portal for investigation.
</x-mail::message>

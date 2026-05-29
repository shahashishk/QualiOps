<x-mail::message>
# Scan Completed

The {{ $scheduled ? 'scheduled' : 'manual' }} scan for **{{ $project->project_name }}** completed successfully.

- Branch: `{{ $history->branch_name }}`
- Scan type: `{{ strtoupper($history->scan_type) }}`
- Status: `{{ strtoupper($history->status) }}`
- Duration: `{{ $history->duration_seconds ?? 'N/A' }}` seconds

<x-mail::button :url="$portalUrl">
Open Scan History
</x-mail::button>

@if (filled($sonarUrl))
<x-mail::button :url="$sonarUrl" color="success">
Open SonarQube
</x-mail::button>
@endif

This message includes only summary data. Review the portal for full details.
</x-mail::message>

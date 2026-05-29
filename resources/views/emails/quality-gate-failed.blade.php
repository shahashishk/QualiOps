<x-mail::message>
# Quality Gate Failed

The latest SonarQube metrics sync for **{{ $project->project_name }}** reports a failing quality gate.

- Project key: `{{ $project->sonar_project_key }}`
- Bugs: `{{ $snapshot->bugs ?? 'N/A' }}`
- Vulnerabilities: `{{ $snapshot->vulnerabilities ?? 'N/A' }}`
- Code smells: `{{ $snapshot->code_smells ?? 'N/A' }}`
- Coverage: `{{ $snapshot->coverage !== null ? $snapshot->coverage.'%' : 'N/A' }}`

<x-mail::button :url="$portalUrl">
Open Project Details
</x-mail::button>

@if (filled($sonarUrl))
<x-mail::button :url="$sonarUrl" color="success">
Open SonarQube
</x-mail::button>
@endif

Review the governance portal for the latest stored snapshot and follow-up actions.
</x-mail::message>

@extends('layouts.admin')

@section('title', 'Create SonarQube Connection')
@section('page_title', 'Create SonarQube Connection')

@section('content')
    <form action="{{ route('sonar-connections.store') }}" method="POST">
        @csrf
        @include('sonar-connections._form', ['submitLabel' => 'Create Connection'])
    </form>
@endsection

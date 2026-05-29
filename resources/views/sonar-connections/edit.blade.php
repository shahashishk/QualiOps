@extends('layouts.admin')

@section('title', 'Edit SonarQube Connection')
@section('page_title', 'Edit SonarQube Connection')

@section('content')
    <form action="{{ route('sonar-connections.update', $connection) }}" method="POST">
        @csrf
        @method('PUT')
        @include('sonar-connections._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection

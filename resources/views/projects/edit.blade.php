@extends('layouts.admin')

@section('title', 'Edit Project')
@section('page_title', 'Edit Project')

@section('content')
    <form action="{{ route('projects.update', $project) }}" method="POST">
        @csrf
        @method('PUT')
        @include('projects._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection

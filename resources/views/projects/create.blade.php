@extends('layouts.admin')

@section('title', 'Create Project')
@section('page_title', 'Create Project')

@section('content')
    <form action="{{ route('projects.store') }}" method="POST">
        @csrf
        @include('projects._form', ['submitLabel' => 'Create Project'])
    </form>
@endsection

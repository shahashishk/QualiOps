@extends('layouts.admin')

@section('title', 'Create Role')
@section('page_title', 'Create Role')

@section('content')
    <form action="{{ route('roles.store') }}" method="POST">
        @csrf
        @include('roles._form', ['submitLabel' => 'Create Role'])
    </form>
@endsection

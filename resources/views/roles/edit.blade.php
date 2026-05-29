@extends('layouts.admin')

@section('title', 'Edit Role')
@section('page_title', 'Edit Role')

@section('content')
    <form action="{{ route('roles.update', $managedRole) }}" method="POST">
        @csrf
        @method('PUT')
        @include('roles._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection

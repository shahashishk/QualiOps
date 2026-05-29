@extends('layouts.admin')

@section('title', 'Edit User')
@section('page_title', 'Edit User')

@section('content')
    <form action="{{ route('users.update', $managedUser) }}" method="POST">
        @csrf
        @method('PUT')
        @include('users._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection

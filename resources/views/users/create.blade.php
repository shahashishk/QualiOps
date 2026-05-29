@extends('layouts.admin')

@section('title', 'Create User')
@section('page_title', 'Create User')

@section('content')
    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        @include('users._form', ['submitLabel' => 'Create User'])
    </form>
@endsection

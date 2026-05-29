@extends('layouts.admin')

@section('title', 'Edit Git Connection')
@section('page_title', 'Edit Git Connection')

@section('content')
    <form action="{{ route('git-connections.update', $connection) }}" method="POST">
        @csrf
        @method('PUT')
        @include('git-connections._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection

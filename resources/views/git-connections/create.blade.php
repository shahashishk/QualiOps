@extends('layouts.admin')

@section('title', 'Create Git Connection')
@section('page_title', 'Create Git Connection')

@section('content')
    <form action="{{ route('git-connections.store') }}" method="POST">
        @csrf
        @include('git-connections._form', ['submitLabel' => 'Create Connection'])
    </form>
@endsection

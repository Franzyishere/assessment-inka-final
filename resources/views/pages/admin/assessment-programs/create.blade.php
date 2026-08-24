@extends('layouts.app')
@section('content')
    <x-common.page-breadcrumb pageTitle="Buat Program Assessment" />
    @include('pages.admin.assessment-programs._form')
@endsection

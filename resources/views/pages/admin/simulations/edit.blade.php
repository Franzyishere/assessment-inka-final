@extends('layouts.app')
@section('content')
    <x-common.page-breadcrumb pageTitle="Edit Simulasi" />
    @include('pages.admin.simulations._form')
@endsection

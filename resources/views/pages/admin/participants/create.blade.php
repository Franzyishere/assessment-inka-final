@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Tambah Peserta Assessment" />
@include('pages.users._form', ['formAction' => route('admin.participants.store'), 'cancelRoute' => route('admin.participants.index'), 'roles' => []])
@endsection

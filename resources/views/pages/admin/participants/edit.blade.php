@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Edit Peserta Assessment" />
@include('pages.users._form', ['formAction' => route('admin.participants.update', $managedUser), 'cancelRoute' => route('admin.participants.index'), 'roles' => [], 'participantMode' => true])
@endsection

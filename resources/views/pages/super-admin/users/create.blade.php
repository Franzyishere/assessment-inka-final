@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="Tambah Pengguna" />
@include('pages.users._form', ['formAction' => route('super-admin.users.store'), 'cancelRoute' => route('super-admin.users.index'), 'roles' => ['super_admin'=>'Super Admin','admin'=>'Admin HCGA','asesor'=>'Asesor','peserta_assessment'=>'Peserta Assessment','peserta_rekrutmen'=>'Peserta Rekrutmen']])
@endsection

@extends('layouts.app')

@section('title', 'Gestión de Personal - IntalnetAcces')
@section('page-header', 'Gestión de Empleados')
@section('page-sub-header', 'Administración de personal importado desde los huelleros ISAPI')

@section('content')
    <livewire:employee-table />
@endsection

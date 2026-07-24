@extends('layouts.app')

@section('title', 'Marcaciones y Reportes - IntalnetAcces')
@section('page-header', 'Reportes de Asistencia')
@section('page-sub-header', 'Filtro en tiempo real y exportación de marcaciones')

@section('content')
<div class="space-y-6">

    <!-- Header Actions (Black & White Flowbite Style) -->
    <div class="bw-card p-5 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm bg-white border border-slate-200">
        <div class="flex items-center space-x-3">
            <div class="p-2.5 bg-slate-100 text-slate-900 rounded-xl border border-slate-300">
                <i data-lucide="clipboard-list" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-heading font-extrabold text-lg text-slate-900">Marcaciones de Asistencia</h3>
                <p class="text-xs text-slate-500 font-medium">Sincronización de registros de lectura dactilar ISAPI en tiempo real</p>
            </div>
        </div>

        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <!-- Botón Sincronizar ISAPI -->
            <form action="{{ route('dashboard.sync') }}" method="POST" class="w-full sm:w-auto">
                @csrf
                <button type="submit" class="bg-black hover:bg-slate-800 text-white font-extrabold rounded-xl text-xs px-4 py-2.5 flex items-center justify-center space-x-1.5 shadow-md w-full sm:w-auto">
                    <i data-lucide="refresh-cw" class="w-4 h-4 text-white"></i>
                    <span>Sincronizar Marcaciones ISAPI</span>
                </button>
            </form>

            <!-- Exportar CSV -->
            <a href="{{ route('attendance.export', request()->query()) }}" class="text-slate-900 bg-white hover:bg-slate-100 border border-slate-300 font-extrabold rounded-xl text-xs px-4 py-2.5 flex items-center justify-center shadow-sm whitespace-nowrap w-full sm:w-auto">
                <i data-lucide="download" class="w-4 h-4 mr-1.5 text-slate-900"></i> Exportar CSV
            </a>
        </div>
    </div>

    <!-- Componente Livewire -->
    <livewire:attendance-table />

</div>
@endsection

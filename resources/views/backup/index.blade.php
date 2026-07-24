@extends('layouts.app')

@section('title', 'Copias de Seguridad - IntalnetAcces')
@section('page-header', 'Copias de Seguridad (Backup)')
@section('page-sub-header', 'Descarga y restauración de la base de datos de asistencia local')

@section('content')
<div class="space-y-6 max-w-4xl">

    <!-- WARNING BANNER CARD -->
    <div class="bg-black text-white p-5 rounded-2xl border border-slate-800 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-start space-x-3">
            <div class="w-10 h-10 rounded-xl bg-white text-black font-extrabold flex items-center justify-center flex-shrink-0 mt-0.5 sm:mt-0 shadow-md">
                <i data-lucide="shield-alert" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-heading font-extrabold text-sm text-white">Gestión Segura de Respaldos</h3>
                <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                    Este panel permite descargar el archivo completo de la base de datos o subir una versión guardada anteriormente. Cualquier restauración **sobrescribirá de forma definitiva** los empleados, huellas y registros actuales.
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        
        <!-- LEFT COLUMN: DB INFO CARD -->
        <div class="md:col-span-5 space-y-6">
            <div class="bw-card p-6 rounded-2xl space-y-5 bg-white border border-slate-200 shadow-sm">
                <div class="flex items-center space-x-3 border-b border-slate-100 pb-4">
                    <div class="p-2.5 bg-slate-100 text-slate-900 rounded-xl border border-slate-300 flex-shrink-0">
                        <i data-lucide="database" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-heading font-extrabold text-sm text-slate-900">Estado de Base de Datos</h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Base de datos SQLite activa</p>
                    </div>
                </div>

                <div class="space-y-4 text-xs font-semibold">
                    <div class="p-3.5 bg-slate-50 border border-slate-200/60 rounded-xl">
                        <span class="text-slate-400 block uppercase font-bold text-[9px] tracking-wider mb-0.5">Tamaño de Base de Datos</span>
                        <span class="text-sm font-black text-slate-900">{{ $db_size }}</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 border border-slate-200/60 rounded-xl">
                        <span class="text-slate-400 block uppercase font-bold text-[9px] tracking-wider mb-0.5">Última Modificación</span>
                        <span class="text-sm font-black text-slate-900">{{ $last_modified }}</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 border border-slate-200/60 rounded-xl overflow-hidden">
                        <span class="text-slate-400 block uppercase font-bold text-[9px] tracking-wider mb-0.5">Ubicación del Archivo</span>
                        <span class="font-mono text-[10px] text-slate-700 block mt-1 break-all leading-tight">
                            {{ $db_path }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: ACTIONS PANEL -->
        <div class="md:col-span-7 space-y-6">
            
            <!-- DOWNLOAD CARD -->
            <div class="bw-card p-6 rounded-2xl bg-white border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between">
                <div class="space-y-2 mb-5">
                    <h3 class="font-heading font-extrabold text-base text-slate-900 flex items-center">
                        <i data-lucide="download" class="w-4 h-4 mr-2 text-slate-900"></i>
                        Descargar Copia de Seguridad
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-medium">
                        Crea y descarga un archivo instantáneo comprimido en formato `.zip` que contiene toda la información histórica de IntalnetAcces. Se recomienda guardarlo en una unidad externa o nube.
                    </p>
                </div>

                <div>
                    <a href="{{ route('backups.download') }}" class="btn-hover-grow icon-bounce-hover inline-flex items-center justify-center space-x-2 bg-black hover:bg-slate-800 text-white font-extrabold text-xs px-5 py-3 rounded-xl shadow-md w-full sm:w-auto">
                        <i data-lucide="download" class="w-4 h-4 text-white"></i>
                        <span>Generar y Descargar Respaldo</span>
                    </a>
                </div>
            </div>

            <!-- RESTORE CARD -->
            <div class="bw-card p-6 rounded-2xl bg-white border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="space-y-2 mb-4 border-b border-slate-100 pb-4">
                    <h3 class="font-heading font-extrabold text-base text-slate-900 flex items-center">
                        <i data-lucide="upload" class="w-4 h-4 mr-2 text-slate-900"></i>
                        Restaurar Copia de Seguridad
                    </h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-medium">
                        Sube un archivo `.sqlite`, `.db` o `.zip` comprimido que hayas descargado anteriormente para restablecer el sistema a ese punto temporal específico.
                    </p>
                </div>

                <!-- Formulario de Restauración -->
                <form id="restoreForm" action="{{ route('backups.restore') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    
                    <!-- File input styled as a drag-and-drop zone -->
                    <div class="flex items-center justify-center w-full">
                        <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100/60 transition-all">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i data-lucide="file-up" class="w-8 h-8 text-slate-400 mb-2"></i>
                                <p class="text-xs text-slate-500 font-bold" id="fileNamePlaceholder">
                                    <span class="text-black underline">Haz clic para buscar</span> o arrastra el archivo aquí
                                </p>
                                <p class="text-[10px] text-slate-400 font-semibold mt-1">Archivos SQLite (.sqlite, .db) o comprimidos (.zip) - Máx 50MB</p>
                            </div>
                            <input id="dropzone-file" name="backup_file" type="file" required accept=".sqlite,.db,.zip" class="hidden" onchange="updateFileLabel(this)" />
                        </label>
                    </div>

                    <!-- Trigger Button for Flowbite Modal Confirmation -->
                    <button type="button" onclick="openConfirmationModal()" class="btn-hover-grow w-full text-slate-900 bg-white hover:bg-slate-100 border border-slate-300 font-extrabold rounded-xl text-xs py-3 flex items-center justify-center space-x-2 shadow-sm">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-slate-900"></i>
                        <span>Restaurar Base de Datos</span>
                    </button>
                </form>
            </div>
            
        </div>
    </div>
</div>

<!-- FLOWBITE CONFIRMATION MODAL -->
<div id="confirmRestoreModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-slate-900/80 backdrop-blur-sm">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-300">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-slate-200 rounded-t bg-slate-50">
                <h3 class="text-base font-extrabold text-slate-900 flex items-center">
                    <i data-lucide="alert-triangle" class="w-5 h-5 mr-2 text-red-600"></i>
                    ¿Confirmar Restauración?
                </h3>
                <button type="button" onclick="closeConfirmationModal()" class="text-slate-400 bg-transparent hover:bg-slate-200 hover:text-slate-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <!-- Body -->
            <div class="p-6 space-y-4">
                <div class="p-3.5 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs space-y-1">
                    <p class="font-extrabold uppercase tracking-wider text-[10px]">¡Advertencia de Sobrescritura!</p>
                    <p class="font-medium leading-relaxed">
                        Esta acción es irreversible una vez completada. La base de datos actual será reemplazada por el archivo seleccionado. Asegúrate de tener un respaldo de la versión actual si deseas recuperarla en el futuro.
                    </p>
                </div>
                <p class="text-xs text-slate-600 font-bold text-center" id="selectedFileConfirmationText">
                    Archivo seleccionado: ...
                </p>
            </div>
            <!-- Footer -->
            <div class="flex items-center justify-end space-x-3 p-4 border-t border-slate-200 bg-slate-50 rounded-b">
                <button type="button" onclick="closeConfirmationModal()" class="text-slate-600 bg-slate-100 hover:bg-slate-200 font-bold rounded-xl text-xs px-4 py-2.5 border border-slate-300">
                    Cancelar
                </button>
                <button type="button" onclick="submitRestoreForm()" class="text-white bg-black hover:bg-slate-800 font-extrabold rounded-xl text-xs px-5 py-2.5 shadow-md flex items-center space-x-1.5">
                    <i data-lucide="check-circle" class="w-4 h-4 text-white"></i>
                    <span>Sí, Restaurar Base de Datos</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
{
    let confirmModal = null;

    // Inicializar el modal de Flowbite al cargar la página
    document.addEventListener('livewire:navigated', () => {
        const modalEl = document.getElementById('confirmRestoreModal');
        if (modalEl && typeof Modal !== 'undefined') {
            confirmModal = new Modal(modalEl, {
                placement: 'center',
                backdrop: 'dynamic',
                closable: true
            });
        }
    });

    window.updateFileLabel = function(input) {
        const placeholder = document.getElementById('fileNamePlaceholder');
        if (input.files && input.files[0]) {
            const fileName = input.files[0].name;
            const fileSize = (input.files[0].size / 1024).toFixed(1) + ' KB';
            placeholder.innerHTML = `<span class="text-black font-black block">${fileName}</span> <span class="text-slate-400 text-[10px] font-bold">(${fileSize})</span>`;
        } else {
            placeholder.innerHTML = `<span class="text-black underline">Haz clic para buscar</span> o arrastra el archivo aquí`;
        }
    }

    window.openConfirmationModal = function() {
        const fileInput = document.getElementById('dropzone-file');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Por favor selecciona un archivo de respaldo antes de continuar.');
            return;
        }

        const fileName = fileInput.files[0].name;
        document.getElementById('selectedFileConfirmationText').innerHTML = `Archivo seleccionado:<br><strong class="text-black text-sm">${fileName}</strong>`;

        if (!confirmModal) {
            const modalEl = document.getElementById('confirmRestoreModal');
            if (modalEl && typeof Modal !== 'undefined') {
                confirmModal = new Modal(modalEl, {
                    placement: 'center',
                    backdrop: 'dynamic',
                    closable: true
                });
            }
        }

        if (confirmModal) {
            confirmModal.show();
        }
    }

    window.closeConfirmationModal = function() {
        if (confirmModal) {
            confirmModal.hide();
        }
    }

    window.submitRestoreForm = function() {
        closeConfirmationModal();
        
        // Mostrar indicador de carga / pantalla de bloqueo corporativa de restauración
        const submitBtn = document.querySelector('#restoreForm button[type="button"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Procesando Restauración...</span>
            `;
        }

        document.getElementById('restoreForm').submit();
    };
}
</script>
@endpush
@endsection

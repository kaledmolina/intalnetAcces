<div class="space-y-4">
    <!-- Filter Panel -->
    <div class="bw-card p-5 rounded-2xl shadow-sm space-y-4 bg-white border border-slate-200">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Search -->
            <div>
                <label class="block text-[11px] font-extrabold uppercase text-slate-700 mb-1">Buscar Empleado</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nombre o ID..." class="bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-xl focus:ring-black focus:border-black block w-full pl-9 p-2.5">
                </div>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-[11px] font-extrabold uppercase text-slate-700 mb-1">Fecha Desde</label>
                <input type="date" wire:model.live="startDate" class="bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-xl focus:ring-black focus:border-black block w-full p-2.5">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-[11px] font-extrabold uppercase text-slate-700 mb-1">Fecha Hasta</label>
                <input type="date" wire:model.live="endDate" class="bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-xl focus:ring-black focus:border-black block w-full p-2.5">
            </div>

            <!-- Toggle Tardanzas -->
            <div class="flex flex-col justify-end">
                <label class="inline-flex items-center cursor-pointer p-2.5 rounded-xl bg-slate-50 border border-slate-300">
                    <input type="checkbox" wire:model.live="onlyLate" class="sr-only peer">
                    <div class="relative w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
                    <span class="ms-3 text-xs font-extrabold text-slate-900">Solo Tardanzas</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Black & White Corporate Attendance Table -->
    <div class="bw-card rounded-2xl overflow-hidden shadow-sm bg-white border border-slate-200">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-800">
                <thead class="text-xs uppercase bg-slate-100 text-slate-700 border-b border-slate-200 font-extrabold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-3.5">Empleado</th>
                        <th scope="col" class="px-6 py-3.5">Fecha y Hora</th>
                        <th scope="col" class="px-6 py-3.5">Huellero ISAPI</th>
                        <th scope="col" class="px-6 py-3.5">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($records as $record)
                        <tr class="bg-white hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-black font-extrabold text-white text-xs flex items-center justify-center">
                                        {{ strtoupper(substr($record->employee ? $record->employee->name : $record->employee_no, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-slate-900 leading-tight">
                                            {{ $record->employee ? $record->employee->name : 'Empleado #' . $record->employee_no }}
                                        </p>
                                        <p class="text-xs text-slate-500 font-mono font-semibold mt-0.5">ID: #{{ $record->employee_no }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-bold text-slate-900">{{ $record->event_time->format('d/m/Y') }}</span>
                                <span class="text-xs text-slate-600 block font-mono font-extrabold mt-0.5">{{ $record->event_time->format('h:i:s A') }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-black text-white text-xs font-extrabold px-2.5 py-1 rounded-lg border border-black inline-flex items-center shadow-sm">
                                    <i data-lucide="cpu" class="w-3.5 h-3.5 mr-1.5 text-white"></i>
                                    {{ $record->device ? $record->device->name : 'Huellero' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($record->is_late)
                                    <span class="bg-slate-200 text-slate-900 text-xs font-extrabold px-2.5 py-1 rounded-full border border-slate-400 inline-flex items-center">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 mr-1"></i>
                                        Tardanza (+{{ $record->late_minutes }} min)
                                    </span>
                                @else
                                    <span class="bg-black text-white text-xs font-extrabold px-2.5 py-1 rounded-full border border-black inline-flex items-center shadow-sm">
                                        <i data-lucide="check" class="w-3.5 h-3.5 mr-1 text-white"></i>
                                        Puntual
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-500 font-medium">
                                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-400"></i>
                                No se encontraron marcaciones que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-t border-slate-200 bg-white">
                {{ $records->links('partials.pagination') }}
            </div>
        @endif
    </div>
</div>

<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\Device;
use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceTable extends Component
{
    use WithPagination;

    public $search = '';
    public $employeeId = '';
    public $deviceId = '';
    public $onlyLate = false;
    public $startDate = '';
    public $endDate = '';

    public function mount()
    {
        $this->startDate = now()->subDays(30)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = AttendanceRecord::with(['employee', 'device']);

        if (!empty($this->startDate)) {
            $query->whereDate('event_time', '>=', $this->startDate);
        }
        if (!empty($this->endDate)) {
            $query->whereDate('event_time', '<=', $this->endDate);
        }
        if (!empty($this->employeeId)) {
            $query->where('employee_id', $this->employeeId);
        }
        if (!empty($this->deviceId)) {
            $query->where('device_id', $this->deviceId);
        }
        if ($this->onlyLate) {
            $query->where('is_late', true);
        }
        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_no', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $records = $query->latest('event_time')->paginate(15);
        $employees = Employee::orderBy('name')->get();
        $devices = Device::all();

        return view('livewire.attendance-table', [
            'records' => $records,
            'employees' => $employees,
            'devices' => $devices,
        ]);
    }
}

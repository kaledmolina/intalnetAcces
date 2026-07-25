<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Device;
use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeTable extends Component
{
    use WithPagination;

    public $search = '';
    public $departmentId = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDepartmentId()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Employee::with('department');

        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%")
                  ->orWhere('document_id', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        if (!empty($this->departmentId)) {
            $query->where('department_id', $this->departmentId);
        }

        $employees = $query->orderBy('employee_no')->paginate(15);
        $departments = Department::orderBy('name')->get();
        $deviceCount = Device::count();

        return view('livewire.employee-table', [
            'employees' => $employees,
            'departments' => $departments,
            'deviceCount' => $deviceCount,
        ]);
    }
}

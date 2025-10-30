@extends('layouts.app')  {{-- Asumsikan Anda memiliki layout utama --}}

@section('content')
<div class="container">
    <h2 class="mb-4">Attendance</h2>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered data-table">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Staff ID</th>
                    <th>Attendance Type</th>
                    <th>Biometric</th>
                    <th>Authorized</th>
                    <th>Device Data</th>
                    <th>Remark</th>
                    <th>Recorded At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->id }}</td>
                        <td>{{ $attendance->date }}</td>
                        <td>{{ $attendance->staff_id }}</td>
                        <td>
                            <span class="badge bg-{{ $attendance->staff_attendance_type_id == 1 ? 'success' : 'warning' }}">
                                Type {{ $attendance->staff_attendance_type_id }}
                            </span>
                        </td>
                        <td>
                            @if($attendance->biometric_attendence)
                                <span class="badge bg-info">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td>
                            @if($attendance->is_authorized_range)
                                <span class="badge bg-success">Authorized</span>
                            @else
                                <span class="badge bg-danger">Unauthorized</span>
                            @endif
                        </td>
                        <td>
                            @if($attendance->biometric_device_data)
                                <button type="button" class="btn btn-sm btn-info"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deviceDataModal{{ $attendance->id }}">
                                    View Details
                                </button>

                                <!-- Modal -->
                                <div class="modal fade" id="deviceDataModal{{ $attendance->id }}" tabindex="-1" aria-labelledby="deviceDataModalLabel{{ $attendance->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deviceDataModalLabel{{ $attendance->id }}">Device Data</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <pre>{{ json_encode(json_decode($attendance->biometric_device_data), JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>{{ $attendance->remark }}</td>
                        <td>{{ $attendance->created_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            <p class="my-3">No attendance records found. Attendance data will appear here once biometric devices send data.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- source: https://stackoverflow.com/a/70119390 -->
    <div class="d-flex justify-content-center">
        {{ $attendances->links() }}  {{-- Tampilkan pagination jika ada --}}
    </div>


</div>
@endsection
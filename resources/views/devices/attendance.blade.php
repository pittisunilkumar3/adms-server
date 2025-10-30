@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Biometric Attendance Records (Staff & Students)</h2>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-striped data-table">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>User Type</th>
                    <th>Name</th>
                    <th>ID/Admission No</th>
                    <th>Date</th>
                    <th>Biometric</th>
                    <th>Authorized</th>
                    <th>Remark</th>
                    <th>Recorded At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->id }}</td>
                        <td>
                            @if($attendance->user_type === 'staff')
                                <span class="badge bg-primary">Staff</span>
                            @else
                                <span class="badge bg-success">Student</span>
                            @endif
                        </td>
                        <td>{{ $attendance->user_name }}</td>
                        <td>{{ $attendance->user_identifier }}</td>
                        <td>{{ $attendance->date }}</td>
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

    <div class="d-flex justify-content-center">
        {{ $attendances->links() }}
    </div>

</div>
@endsection
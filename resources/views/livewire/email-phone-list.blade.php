<div>
    <div>
        {{-- Because she competes with no one, no one can compete with her. --}}
    </div>

    <div>
        <h2 class="text-xl font-bold mb-4">Emails & Phones</h2>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Temp Pass</th>
                        <th>Phone Number</th>
                        <th>Email Type</th>
                        <th>Phone Type</th>
                        <th>Email Primary</th>
                        <th>Phone Primary</th>
                        <th>Email Verified</th>
                        <th>Phone Verified</th>
                        <th>Email Status</th>
                        <th>Phone Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($emails as $index => $email)
                        <tr>
                            <td>{{ $email->email }}</td>
                            <td>{{ $email->user->temporary_password ?? 'N/A' }}</td>

                            <td>{{ $phones[$index]->number ?? 'N/A' }}</td>
                            <td>{{ $email->type }}</td>
                            <td>{{ $phones[$index]->type ?? 'N/A' }}</td>
                            <td>{{ $email->is_primary ? 'Yes' : 'No' }}</td>
                            <td>{{ $phones[$index]->is_primary ? 'Yes' : 'No' }}</td>
                            <td>{{ $email->is_verified ? 'Yes' : 'No' }}</td>
                            <td>{{ $phones[$index]->is_verified ? 'Yes' : 'No' }}</td>
                            <td>{{ $email->status }}</td>
                            <td>{{ $phones[$index]->status ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-bold mt-8 mb-4">Organization Contacts</h2>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Organization Name</th>
                        <th>Contact Email</th>
                        <th>Contact Phone</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($organizations as $organization)
                        <tr>
                            <td>{{ $organization->legal_name }}</td>
                            <td>{{ $organization->contact_email ?? 'N/A' }}</td>
                            <td>{{ $organization->contact_phone ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

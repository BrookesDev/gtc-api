<div>

    <div class="table-responsive">
        <table id="example" class="table table-striped table-bordered nowrap  dt-responsive">
            <thead>
                <tr class="ligth">
                    <th>SN</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Role</th>
                    <th>Created At</th>

                    <th style="min-width: 100px">Action</th>
                </tr>
            </thead>
            <tbody>

                @foreach ($users as $user)
                    <tr>
                        <td>{{ $loop->iteration }}  </td>
                        <td>
                            {{ $user->name }}
                        </td>
                        <td>
                            {{ $user->email }}
                        </td>
                        <td>
                            {{ $user->phone_umber }}
                        </td>
                        <td>
                            @if (!empty($user->getRoleNames()))
                            @foreach ($user->getRoleNames() as $v)
                                <span class="text-info">{{ $v }}</span>
                            @endforeach
                        @endif
                        </td>
                        <td>{{ date('d-M-Y', strtotime($user->created_at)) }}</td>
                        <td>
                            <a href="#" class="btn btn-success-soft btn-sm mr-1" data-id="{{ $user->id }}" data-toggle="modal" data-target="#modal-edit" id="edit-user"><i
                                    class="far fa-eye"></i></a>
                            <a href="#" class="btn btn-danger-soft btn-sm" id="deleteRecord" data-id="{{ $user->id }}" data-username="{{ $user->first_name.' '.$user->last_name }}"><i
                                    class="far fa-trash-alt"></i></a>
                        </td>
                    </tr>
                @endforeach


            </tbody>
        </table>
    </div>
</div>

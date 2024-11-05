<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fs-17 font-weight-600 mb-0 textInput">All Rewards</h6>
            </div>
            <div class="text-right">
                <button type="button" class="btn btn-success rounded-pill w-100p btn-sm mr-1" data-toggle="modal" data-target="#exampleModal1">Create New Reward</button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="mailbox-content">

            @forelse($rewards as $reward)
            <div data-href="mailbox_details.html"  class="inbox_item d-flex align-items-center unread">
                <img @if($reward->media) src="{{$reward->media}}" @else src="https://cdn-icons-png.flaticon.com/512/5219/5219258.png" @endif class="inbox-avatar d-none d-xl-block mr-2" alt="">
                <div class="inbox-avatar-text">
                    <h6 class="avatar-name fs-15 font-weight-600 mb-0">{{ $reward->reward }}: <b class="text-right">{{  $reward->point}} Point(s)</b></h6>
                    <div><span><strong></strong><span class="badge badge-{{ $reward->style() }}">{{ $reward->status }}</span>
                        </span></span>
                        
                    </div>
                </div>
                <div class="inbox-date d-none d-xl-block ml-auto">
                    <div class="date">{{ \Carbon\Carbon::parse($reward->created_at)->format('g:i A') }}</div>
                    <div><small>{{ \Carbon\Carbon::parse($reward->created_at)->format('F jS') }}</small></div>
                    <a href="javascript:void(0)" data-toggle="modal" data-target="#exampleModal11" id="eReward" data-id="{{$reward->id}}"  class="btn btn-success-soft btn-sm mr-1"><i class="far fa-eye"></i></a>
                    @if($reward->status == "Active")
                    <a href="javascript:void(0)" id="disableRecord" data-id="{{$reward->id}}" data-value="InActive" class="btn btn-warning-soft btn-sm mr-1"><i class="fa fa-exclamation"></i></a>
                    @else
                    <a href="javascript:void(0)" id="disableRecord" data-id="{{$reward->id}}" data-value="Active" class="btn btn-primary-soft btn-sm"><i class="fa fa-share"></i></a>
                    @endif
                </div>
            </div>
            <br>
            {{ $rewards->links() }}
            @empty
            <li>No record found.</li>
            @endforelse

        </div>
    </div>
    {{-- create new inventory --}}
    <div class="modal fade" id="exampleModal1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Add New Reward</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('admin.create.reward')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Reward</label>
                            <input type="text" name="reward" required class="form-control" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter reward name">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Point</label>
                            <input type="number" class="form-control" required name="point" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter point required to claim reward">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Media </label>
                            <input type="file" name="media" accept="image/*" class="form-control" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Choose product image">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Status</label>
                            <select class="form-control form-control-lg mt-2 basic-single" name="status" required >
                                    <option selected value="">Choose Status</option>
                                    <option  value="Active">Active</option>
                                    <option  value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger " data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- edit inventory  --}}
    <div class="modal fade" id="exampleModal11" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600 VHead" id="exampleModalLabel4 VHead"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('admin.update.reward')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="VId">
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Reward</label>
                            <input type="text" name="reward" required class="form-control Rreward" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter reward name">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Point</label>
                            <input type="number" class="form-control Rpoint" required name="point" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter point required to claim reward">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Media </label>
                            <input type="file" name="media" accept="image/*" class="form-control" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Choose product image">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Status</label>
                            <select class="form-control form-control-lg mt-2  Rstatus" name="status" required >
                                    <option  value="">Choose Status</option>
                                    <option  value="Active">Active</option>
                                    <option  value="InActive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger " data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

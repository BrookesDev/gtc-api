@if($allMessages == 1)
<div class="body-content">
    <div class="mailbox">
        <div class="mailbox-header d-flex align-items-center justify-content-between">
            <div class="inbox-avatar-wrap d-flex align-items-center"><img src="{{ config('app.url') }}needs-admin/assets/dist/img/avatar-1.jpg" class="inbox-avatar border-green" alt="">
                <div class="inbox-avatar-text d-none d-sm-inline-block ml-3">
                    <h6 class="avatar-name mb-0">{{ auth()->user()->first_name.' '.auth()->user()->last_name }}</h6>
                    <span>Mailbox</span>
                </div>
            </div>
            <div class="inbox-toolbar btn-toolbar">
                <div class="btn-group">
                    <a wire:click.prevent='createNewMessage' href="" class="btn btn-success"><i class="far fa-edit"></i></a>
                </div>
            </div>
        </div>
        <div class="mailbox-body">
            <div class="row m-0">
                <div class="col-lg-3 p-0 inbox-nav d-none d-lg-block">
                    <div class="mailbox-sideber">
                        <div class="profile-usermenu">
                            <h6 class="fs-13 font-weight-bold">Mailbox</h6>
                            <ul class="nav flex-column">
                                <li class="nav-item active"><a href="" wire:click.prevent='createNewMessage'><i class="typcn typcn-mail"></i>Sent Mail</a></li>
                                <li class="nav-item "><a href="#"><i class="fa fa-inbox"></i>Inbox <small class="label pull-right">0</small></a></li>
                                
                    
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-12 col-lg-9 p-0 inbox-mail">
                    <div class="mailbox-content">

                        @foreach($messages as $message)
                        <div data-href="mailbox_details.html" class="inbox_item d-flex align-items-center unread">
                            <div class="inbox-avatar-text">
                                <h6 class="avatar-name fs-15 font-weight-600 mb-0">{{ $message->to }}: <span class="badge badge-{{ $message->style() }}">{{ $message->status }}</span></h6>
                                <div><span><strong>{{ $message->subject }}: </strong><span> 
                                    {!! $message->message !!}
                                    </span></span>
                                    
                                </div>
                            </div>
                            <div class="inbox-date d-none d-xl-block ml-auto">
                                <div class="date">{{ \Carbon\Carbon::parse($message->created_at)->format('g:i A') }}</div>
                                <div><small>{{ \Carbon\Carbon::parse($message->created_at)->format('F jS') }}</small></div>
                                <a href="javascript:void(0)" data-toggle="modal" data-target="#exampleModal11" id="eMessage" data-id="{{$message->id}}" class="btn btn-success-soft btn-sm mr-1"><i class="far fa-eye"></i></a>
                                @if($message->status == "Active")
                                <a href="javascript:void(0)" id="disableRecord" data-id="{{$message->id}}" data-value="Disabled" class="btn btn-warning-soft btn-sm mr-1"><i class="fa fa-exclamation"></i></a>
                                @endif
                                @if($message->status == "Draft" || $message->status == "Disabled")
                                <a href="javascript:void(0)" id="disableRecord" data-id="{{$message->id}}" data-value="Active" class="btn btn-info-soft btn-sm mr-1"><i class="fa fa-share"></i></a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        <br>
                        {{ $messages->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
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
            <form action="{{route('admin.update.message')}}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" id="VId">
                    <div class="form-group">
                        <label for="exampleInputEmail1" class="font-weight-600">To</label>
                        <select class="form-control form-control-lg mt-2  Rpoint" name="to" required >
                                <option  value="">Choose </option>
                                <option value="Customer">Customer</option>
                                <option value="Vendor">Vendor</option>
                                <option value="Both">All</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1" class="font-weight-600">Subject</label>
                        <input type="text" name="subject" required class="form-control Rreward" id="exampleInputEmail1"
                            aria-describedby="emailHelp" placeholder="message subject here">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1" class="font-weight-600">Message</label>
                        <textarea class="form-control mMessage" name="message" id="" cols="30" rows="5" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1" class="font-weight-600">Status</label>
                        <select class="form-control form-control-lg mt-2  Rstatus" name="status" required >
                                <option  value="">Choose Status</option>
                                <option value="Active">Active</option>
                                <option value="Draft">Draft</option>
                                <option value="Disabled">Disabled</option>
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
</div><!--/.body content-->
@endif

@if($createMessage == 1)
<div class="body-content">
    <div class="mailbox">
        <div class="mailbox-header d-flex align-items-center justify-content-between">
            <div class="inbox-avatar-wrap d-flex align-items-center"><img src="{{ config('app.url') }}needs-admin/assets/dist/img/avatar-1.jpg" class="inbox-avatar border-green" alt="">
                <div class="inbox-avatar-text d-none d-sm-inline-block ml-3">
                    <h6 class="avatar-name mb-0">{{ auth()->user()->first_name }}</h6>
                    <span>Mailbox</span>
                </div>
            </div>
            <div class="inbox-toolbar btn-toolbar">
                
                <div class="btn-group ml-1 d-none d-lg-flex">
                    <button wire:click.prevent='showAllMessages' type="button" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                </div>
            </div>
        </div>
        <div class="mailbox-body">
            <div class="row m-0">
                <div class="col-lg-3 p-0 inbox-nav d-none d-lg-block">
                    <div class="mailbox-sideber">
                        <div class="profile-usermenu">
                            <h6 class="fs-13 font-weight-bold">Mailbox</h6>
                            <ul class="nav flex-column">
                                <li class="nav-item active"><a href="" wire:click.prevent='createNewMessage'><i class="typcn typcn-mail"></i>Sent Mail</a></li>
                                <li class="nav-item "><a href="#"><i class="fa fa-inbox"></i>Inbox <small class="label pull-right">0</small></a></li>
                                
                    
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-lg-9 p-0 inbox-mail p-3">
                    <div class="form-group row">
                        <label class="col-sm-3 col-md-2 col-form-label text-right">To :</label>
                        <div class="col-sm-9 col-md-10">
                            <select wire:model.lazy='to' class="form-control" required>
                                <option value="">-- Select --</option>
                                <option value="Customer">Customer</option>
                                <option value="Vendor">Vendor</option>
                                <option value="Both">All</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-md-2 col-form-label text-right">Subject :</label>
                        <div class="col-sm-9 col-md-10">
                            <input wire:model.lazy='subject' class="form-control" type="text" id="subjejct" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-md-2 col-form-label text-right">Message :</label>
                        <div class="col-sm-9 col-md-10">
                            <textarea wire:model.lazy='message' name="" id="" cols="30" rows="10" required></textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-md-2 col-form-label text-right">Status :</label>
                        <div class="col-sm-9 col-md-10">
                            <select wire:model.lazy='status' class="form-control" required>
                                <option value="">-- Select --</option>
                                <option value="Active">Active</option>
                                <option value="Draft">Draft</option>
                                <option value="Disabled">Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="btn-group pull-right">
                            <button wire:click.prevent='storeMessage' type="button" class="btn btn-success">Create Message</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div><!--/.body content-->
@endif
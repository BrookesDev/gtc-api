<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fs-17 font-weight-600 mb-0 textInput">User Rewards</h6>
            </div>
            <div class="text-right">
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="mailbox-content">

            @forelse($rewards as $reward)
            <div data-href="mailbox_details.html"  class="inbox_item d-flex align-items-center unread">
                <img @if($reward->reward->media) src="{{$reward->reward->media}}" @else src="https://cdn-icons-png.flaticon.com/512/5219/5219258.png" @endif class="inbox-avatar d-none d-xl-block mr-2" alt="">
                <div class="inbox-avatar-text">
                    <h6 class="avatar-name fs-15 font-weight-600 mb-0">{{ $reward->user->first_name . ' ' . $reward->user->last_name }}: <b class="text-right">{{  $reward->reward->reward}} </b></h6>
                    <div><span><strong></strong><span class="badge badge-{{ $reward->style() }}">{{ $reward->status }}</span>
                        <span><span>{{ $reward->reward->point }} Point(s)</span></span>
                        
                    </div>
                </div>
                <div class="inbox-date d-none d-xl-block ml-auto">
                    <div class="date">{{ \Carbon\Carbon::parse($reward->created_at)->format('g:i A') }}</div>
                    <div><small>{{ \Carbon\Carbon::parse($reward->created_at)->format('F jS') }}</small></div>
                    @if($reward->status == "Pending")
                    <a href="javascript:void(0)" id="disableRecord" data-id="{{$reward->id}}" data-value="Redeemed" class="btn btn-primary-soft btn-sm"><i class="fa fa-share"></i></a>
                    <a href="javascript:void(0)" id="disableRecord" data-id="{{$reward->id}}" data-value="Disabled" class="btn btn-warning-soft btn-sm mr-1"><i class="fa fa-exclamation"></i></a>
                    @elseif($reward->status == "Disabled")
                    <a href="javascript:void(0)" id="disableRecord" data-id="{{$reward->id}}" data-value="Redeemed" class="btn btn-primary-soft btn-sm mr-1"><i class="fa fa-share"></i></a>
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

</div>

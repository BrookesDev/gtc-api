<div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fs-17 font-weight-600 mb-0">Referral history</h6>
                            {{-- upate --}}
                        </div>
                        <div class="text-right">
                            <div class="actions">
                                <a href="#" class="action-item"><i class="ti-reload"></i></a>
                                <div class="dropdown action-item" data-toggle="dropdown">
                                    <a href="#" class="action-item"><i class="ti-more-alt"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">

            <ul class="activity-list list-unstyled">
                @forelse($referrers as $referer)
                <li class=activity-purple>
                    <span class="text-muted text-sm">Referral</span>
                    <h5><a class="d-block fs-15 font-weight-600 text-sm mb-0">
                       <span class="text-success"> {{ $referer->referer->first_name .' '. $referer->referer->last_name }}</span>
                        referred <span class="text-success"> {{ $referer->customer->first_name.' '.$referer->customer->last_name }} </span> on
                        {{ date('d-M-Y', strtotime($referer->created_at))}}.

                    </a>
                    </h5>
                    <small class=text-muted><i class="far fa-clock mr-1"></i>
                        {{ $referer->created_at->diffForHumans() }}
                    </small>
                </li>
                @empty
                <p>Empty List!</p>
                @endforelse
            </ul>
                </div>
                <div class="card-footer py-2 text-center">
                    <p>
                        @if ($referrers->hasPages())
                        <div class="pagination-wrapper">
                            {{ $referrers->links('livewire.pagination.custom') }}
                        </div>
                    @endif
                        </p>
                </div>
            </div>


        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fs-17 font-weight-600 mb-0">Customers refer purchase history</h6>
                        </div>
                        <div class="text-right">
                            <div class="actions">
                                <a href="#" class="action-item"><i class="ti-reload"></i></a>
                                <div class="dropdown action-item" data-toggle="dropdown">
                                    <a href="#" class="action-item"><i class="ti-more-alt"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="#" class="dropdown-item">Refresh</a>

                                        <a href="#" class="dropdown-item">Settings</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="activity-list list-unstyled">
                        @forelse($refer_histories as $history)
                        <li class=activity-purple>
                            <span class="text-muted text-sm">Task added</span>
                            <h5><a class="d-block fs-15 font-weight-600 text-sm mb-0">
                                {{ $history->customer->first_name }} purchases an item of
                                &#x20A6;{{ number_format($history->pay_request->amount)  }}.00, from {{ $history->pay_request->vendor->business_name }}, {{ $history->referer->first_name }} earns
                                <span style="color:green">&#x20A6;{{ $history->amount }}.00</span>
                                 for this Transaction.
                            </a>
                            </h5>
                            <small class=text-muted><i class="far fa-clock mr-1"></i>
                                {{ $history->created_at->diffForHumans() }}
                            </small>
                        </li>
                        @empty
                        <p>Empty List!</p>
                        @endforelse
                    </ul>
                </div>
                <div class="card-footer py-2 text-center">
                    <a href="#" class="text-sm text-muted font-weight-bold">{{ $refer_histories->links() }}</a>
                </div>
            </div>
        </div>
    </div>

</div>

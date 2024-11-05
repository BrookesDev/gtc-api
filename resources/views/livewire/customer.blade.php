<div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fs-17 font-weight-600 mb-0">Transaction Log</h6>
                        </div>
                        
                    </div>
                </div>
                <div class="card-body">
                    <ul class="activity-list list-unstyled">
                        @forelse($transactions as $transaction)
                        <li  @if($transaction->tr_type == "cashin") class=activity-purple @else class=activity-warning @endif class=activity-purple>
                            <span class="text-muted text-sm">Transaction</span>
                            <h5><a class="d-block fs-15 font-weight-600 text-sm mb-0">
                                @if($transaction->tr_type == "cashin")
                                <span class="badge badge-pill badge-{{ $transaction->status_color() }}">{{ $transaction->status }}</span>  Cashin of
                                    <span style="color:green">&#x20A6;{{ number_format($transaction->amount, 2)  }}</span> through <span class="badge badge-pill badge-info">{{ $transaction->type }} </span>
                                @else
                                <span class="badge badge-pill badge-{{ $transaction->status_color() }}">{{ $transaction->status }}</span> Cashout of
                                <span style="color:green">&#x20A6;{{ number_format($transaction->amount, 2)  }}</span> through <span class="badge badge-pill badge-info">{{ $transaction->type }} </span>
                                @endif
                            </a>
                            </h5>
                            <small class=text-muted><i class="far fa-clock mr-1"></i>
                                {{ $transaction->created_at->diffForHumans() }}
                            </small>
                        </li>
                        @empty
                        <p>Empty List!</p>
                        @endforelse
                    </ul>
                </div>
                <div class="card-footer py-2 text-center">
                    
                    <a href="#" class="text-sm text-muted font-weight-bold">{{ $transactions->links() }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fs-17 font-weight-600 mb-0">Referral history</h6>
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
                        referred <span class="text-success"> {{ $referer->customer->first_name  .' '. $referer->customer->last_name }} </span> on
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
    </div>

</div>

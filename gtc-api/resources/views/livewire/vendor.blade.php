
        <div class="col-md-6">
            <div class="card mb-4" style="min-height: 631px">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fs-17 font-weight-600 mb-0">Cashout Log</h6>
                        </div>
                        
                    </div>
                </div>
                <div class="card-body">
                    <ul class="activity-list list-unstyled">
                        @forelse($transactions as $transaction)
                        <li  @if($transaction->status == "Success") class=activity-purple @else class=activity-warning @endif class=activity-purple>
                            <span class="text-muted text-sm">Transaction</span>
                            <h5><a class="d-block fs-15 font-weight-600 text-sm mb-0">
                                @if($transaction->status == "Success")
                                    Successful Withdrawal Of
                                    <span style="color:green">&#x20A6;{{ number_format($transaction->amount, 2)  }}</span>
                                @else
                                Pending Withdrawal Of
                                <span style="color:green">&#x20A6;{{ number_format($transaction->amount, 2)  }}</span>
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
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
        
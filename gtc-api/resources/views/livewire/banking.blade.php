<div class="body-content">
    <div class="row">
      <div class="col-md-6 col-lg-3">
          <!--Revenue today indicator--> 
          
          <a href="#" wire:click.prevent="needs_wallet()">
            <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
                <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                    Needs InApp Wallet
                </div>
                <div class="badge badge-primary fs-26 text-monospace mx-auto">&#8358;{{ number_format($needsWallet,2) }}<span class="opacity-50 small"></span></div>
                <div class="text-muted small mt-1">
                    <span class="text-danger">
                        <i class="fas fa fa-long-arrow-alt-down"></i>
                        5% 
                    </span> vs average
                </div>
            </div>
          </a>
              
              
          
          
          
      </div>
      <div class="col-md-6 col-lg-3">
          <!--Revenue today indicator--> 

        <a href="#" wire:click.prevent="alat_wallet()">
          <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
              <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                  Alat Wallet
              </div>
              <div class="badge badge-success fs-26 text-monospace mx-auto">&#8358;{{ number_format($alatWallet,2) }}<span class="opacity-50 small"></span></div>
              <div class="text-muted small mt-1">
                  <span class="text-danger">
                      <i class="fas fa fa-long-arrow-alt-down"></i>
                      5% 
                  </span> vs average
              </div>
          </div>
        </a>


      </div>
      
      
      <div class="col-md-6 col-lg-3">
          <!--Revenue today indicator--> 
          <a href="#" wire:click.prevent="vendor_wallet()">
            <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
                <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                    Vendor Wallet
                </div>
                <div class="badge badge-warning fs-26 text-monospace mx-auto">&#8358;{{ number_format($vendorWallet,2) }}<span class="opacity-50 small"></span></div>
                
                <div class="text-muted small mt-1">
                    <span class="text-danger">
                        <i class="fas fa fa-long-arrow-alt-down"></i>
                        5% 
                    </span> vs average
                </div>
            </div>
          </a>
          
      </div>
      <div class="col-md-6 col-lg-3">
          <!--Revenue today indicator--> 
          <a href="{{ route('referral_index') }}">
            <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
                <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                    Refer Wallet
                </div>
                <div class="badge badge-default fs-26 text-monospace mx-auto">&#8358;{{ number_format($referWallet,2) }}<span class="opacity-50 small"></span></div>
                
                <div class="text-muted small mt-1">
                    <span class="text-danger">
                        <i class="fas fa fa-long-arrow-alt-down"></i>
                        5% 
                    </span> vs average
                </div>
            </div>
          </a>
          
      </div>  
      <div class="col-md-6 col-lg-3">
        <!--Revenue today indicator--> 
        <a href="{{ route('admin.transactions') }}">
        <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
            <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                Credit (Bank Transfer)
            </div>
            <div class="badge badge-success fs-26 text-monospace mx-auto">&#8358;{{ number_format($creditBankTransfer,2) }}<span class="opacity-50 small"></span></div>
            
            <div class="text-muted small mt-1">
                <span class="text-danger">
                    <i class="fas fa fa-long-arrow-alt-down"></i>
                    5% 
                </span> vs average
            </div>
        </div>
    </a>
    </div>
    <div class="col-md-6 col-lg-3">
      <!--Revenue today indicator--> 
      <a href="{{ route('admin.transactions') }}">
      <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
          <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
              Credit (Card Payment)
          </div>
          <div class="badge badge-info fs-26 text-monospace mx-auto">&#8358;{{ number_format($creditCardPayment,2) }}<span class="opacity-50 small"></span></div>
          
          <div class="text-muted small mt-1">
              <span class="text-danger">
                  <i class="fas fa fa-long-arrow-alt-down"></i>
                  5% 
              </span> vs average
          </div>
      </div>
      </a>
    </div>
  
    <div class="col-md-6 col-lg-3">
      <!--Revenue today indicator--> 
      <a href="{{ route('admin.transactions') }}">
      <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
          <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
              Credit (W2W)
          </div>
          <div class="badge badge-primary fs-26 text-monospace mx-auto">&#8358;{{ number_format($creditW2W,2) }}<span class="opacity-50 small"></span></div>
          
          <div class="text-muted small mt-1">
              <span class="text-danger">
                  <i class="fas fa fa-long-arrow-alt-down"></i>
                  5% 
              </span> vs average
          </div>
      </div>
      </a>
    </div>
  
    <div class="col-md-6 col-lg-3">
      <!--Revenue today indicator--> 
      <a href="{{ route('admin.transactions') }}">
      <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
          <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
              Wallet Debits
          </div>
          <div class="badge badge-danger fs-26 text-monospace mx-auto">&#8358;{{ number_format($walletDebit,2) }}<span class="opacity-50 small"></span></div>
          
          <div class="text-muted small mt-1">
              <span class="text-danger">
                  <i class="fas fa fa-long-arrow-alt-down"></i>
                  5% 
              </span> vs average
          </div>
      </div>

      </a>
    </div>
  
    <div class="col-md-6 col-lg-3">
      <!--Revenue today indicator--> 
      <a href="#" wire:click.prevent="vendor_wallet()">
      <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
          <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
              Vendor (Cashout Request)
          </div>
          <div class="badge badge-warning fs-26 text-monospace mx-auto">&#8358;{{ number_format($vendorCashoutRequest,2) }}<span class="opacity-50 small"></span></div>
          
          <div class="text-muted small mt-1">
              <span class="text-danger">
                  <i class="fas fa fa-long-arrow-alt-down"></i>
                  5% 
              </span> vs average
          </div>
      </div>
      </a>
    </div>
  
    <div class="col-md-6 col-lg-3">
      <!--Revenue today indicator--> 
      <a href="#" wire:click.prevent="vendor_wallet()">
      <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
          <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
              Vendor (Settlement)
          </div>
          <div class="badge badge-primary fs-26 text-monospace mx-auto">&#8358;{{ number_format($vendorSettlement,2) }}<span class="opacity-50 small"></span></div>
          
          <div class="text-muted small mt-1">
              <span class="text-danger">
                  <i class="fas fa fa-long-arrow-alt-down"></i>
                  5% 
              </span> vs average
          </div>
      </div>
      </a>
    </div>
    </div>
  
    <hr>

    @if($needs_commission == 1)
    @include('admin.banking.include.needs_commission')
    @endif

    @if($alat_commission == 1)
    @include('admin.banking.include.alat_commission')
    @endif

    @if($vendor_wallet == 1)
    @include('admin.banking.include.vendor_wallets')
    @endif
  
    
</div>

<div class="body-content">
    <div class="row">
        <div class="col-md-6 col-lg-3">
            <a href="#" wire:click.prevent="all()">
                <!--Revenue today indicator--> 
                <div  class="p-2 bg-white rounded p-3 mb-3 shadow-sm ">
                    <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                        Total Sales (All time)
                    </div>
                    <div class="badge badge-warning fs-26 text-monospace mx-auto">&#8358;{{ number_format($details->total_sales(), 2) }}<span class="opacity-50 small"></span></div>
                    <div class="text-muted small mt-1">
                        <span class="text-danger">
                            <i class="fas fa "></i>
                        
                        </span> 
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="#" wire:click.prevent="monthly()">
                <!--Revenue today indicator--> 
                <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
                    <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                        This Month Sales
                    </div>
                    <div class="badge badge-primary fs-26 text-monospace mx-auto">&#8358;{{ number_format($details->current_month_sales(), 2) }}<span class="opacity-50 small"></span></div>
                    <div class="text-muted small mt-1">
                        <span class="text-danger">
                            <i class="fas fa "></i>
                        
                        </span> 
                    </div>
                </div>
            </a>
        </div>   
        <div class="col-md-6 col-lg-3">
            <a href="#" wire:click.prevent="weekly()">
                <!--Revenue today indicator--> 
                <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
                    <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                        This Week Sales  
                    </div>
                    <div class="badge badge-dark fs-26 text-monospace mx-auto">&#8358;{{ number_format($details->current_week_sales(), 2) }}<span class="opacity-50 small"></span></div>
                    <div class="text-muted small mt-1">
                        <span class="text-danger">
                            <i class="fas fa "></i>
                        
                        </span> 
                    </div>
                </div>
            </a>
        </div>    
        <div class="col-md-6 col-lg-3">
            <a href="#" wire:click.prevent="daily()">
                <!--Revenue today indicator--> 
                <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
                    <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                        Today Sales   
                    </div>
                    <div class="badge badge-info fs-26 text-monospace mx-auto">&#8358;{{ number_format($details->today_sales(), 2) }}<span class="opacity-50 small"></span></div>
                    <div class="text-muted small mt-1">
                        <span class="text-danger">
                            <i class="fas fa "></i>
                        
                        </span> 
                    </div>
                </div>
            </a>
        </div>    
    </div>
    <div wire:loading.remove>
    @if($all_sales == 1)
    @include('admin.sales.include.all_sales')
    <script>
        $(document).ready(function() {
        $('#allSalesTable').DataTable();
    });
    </script>
    @endif
    </div>
    <div wire:loading.remove>
    @if($monthly_sales == 1)
    @include('admin.sales.include.monthly_sales')
    <script>
        $(document).ready(function() {
        $('#monthlySalesTable').DataTable();
    });
    </script>
    @endif
    </div>
    <div wire:loading.remove>
    @if($weekly_sales == 1)
    @include('admin.sales.include.weekly_sales')
    <script>
        $(document).ready(function() {
        $('#weeklySalesTable').DataTable();
    });
    </script>
    @endif
    </div>
    <div wire:loading.remove>
    @if($daily_sales == 1)
    @include('admin.sales.include.daily_sales')
    <script>
        $(document).ready(function() {
        $('#dailySalesTable').DataTable();
    });
    </script>
    @endif
    </div>
    
</div>










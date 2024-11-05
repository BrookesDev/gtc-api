<div class="row">
    <div class="col-md-6 col-lg-3">
        <a id="cardVariable" style="cursor: pointer;" data-id="all">
        <!--Revenue today indicator-->
        <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
            <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                All Vendors
            </div>
            <div class="badge badge-primary fs-26 text-monospace mx-auto allV"></div>
            <div class="text-muted small mt-1">
                <span class="text-danger">
                    <i class="fas fa "></i>

                </span>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a wire:click.prevent="verified()" style="cursor: pointer;" data-id="verified">
        <!--Revenue today indicator-->
        <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
            <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                Verified Vendors
            </div>
            <div class="badge badge-success fs-26 text-monospace mx-auto verifiedV"></div>
            <div class="text-muted small mt-1">
                <span class="text-danger pVerify">
                    <i class="fas fa fa-long-arrow-alt-down"></i>

                </span>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a id="cardVariable" style="cursor: pointer;" data-id="unverified">
        <!--Revenue today indicator-->
        <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
            <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                Unverified Vendors
            </div>
            <div class="badge badge-warning fs-26 text-monospace mx-auto unVerifiedV"></div>
            <div class="text-muted small mt-1">
                <span class="text-danger pUnverified">
                    <i class="fas fa fa-long-arrow-alt-down"></i>

                </span>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a id="cardVariable" style="cursor: pointer;" data-id="deleted">
        <!--Revenue today indicator-->
        <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
            <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                Deleted Vendors
            </div>
            <div class="badge badge-danger fs-26 text-monospace mx-auto deletedV"></div>
            <div class="text-muted small mt-1">
                <span class="text-danger pDeleted">
                    <i class="fas fa fa-long-arrow-alt-down"></i>

                </span>
            </div>
        </div>
        </a>
    </div>
</div>
<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fs-17 font-weight-600 mb-0 textInput">All Vendors</h6>
            </div>
            <div class="text-right">
                <button type="button" class="btn btn-success rounded-pill w-100p btn-sm mr-1" data-toggle="modal" data-target="#exampleModal1">Add New Vendor</button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-striped table-bordered dt-responsive nowrap"
        style="border-collapse: collapse; border-spacing: 0; width: 100%;" id="example2">
            <thead>
                <tr>
                    <th>SN</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
    {{-- add new vendor  --}}
    <div class="modal fade" id="exampleModal1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Add New Vendor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addNewVendor" method="post">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="user_type" value="Vendor">
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">First Name</label>
                            <input type="text" name="first_name" required class="form-control" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter first name">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" required class="font-weight-600">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter last name">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Email address</label>
                            <input type="email" class="form-control" required name="email" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter email">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" required class="font-weight-600">Phone Number </label>
                            <input type="number" name="phone" class="form-control" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter phone number">
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
    {{-- add inventory for vendor  --}}
    <div class="modal fade" id="exampleModal11" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600 headerInventory" id="exampleModalLabel4 headerInventory">Add Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('admin.create.vendor.inventory')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="VendorId">
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Product Name</label>
                            <input type="text" name="product_name" required class="form-control" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter product name">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Product Price</label>
                            <input type="number" class="form-control" required name="product_price" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter product price">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Product Image </label>
                            <input type="file" name="product_image" accept="image/*" class="form-control" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Choose product image">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Description</label>
                            <textarea rows="4" cols="80" name="description" class="form-control" placeholder="Here can be your product description"></textarea>
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
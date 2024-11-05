   

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fs-17 font-weight-600 mb-0 textInput">All Inventories</h6>
                </div>
                <div class="text-right">
                    <button type="button" class="btn btn-success rounded-pill w-100p btn-sm mr-1" data-toggle="modal"
                        data-target="#exampleModal1">Add New Inventory</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table id="example" class="table table-striped table-bordered dt-responsive nowrap"
                style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                <thead>
                    <tr>
                        <th>SN</th>
                        <th>Vendor </th>
                        <th>Name </th>
                        <th>Price</th>
                        <th>Image</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inventories as $inventory)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $inventory->vendor->first_name . ' ' . $inventory->vendor->last_name }}</td>
                            <td>{{ $inventory->product_name }}</td>
                            <td class="text-right">{{ number_format($inventory->product_price, 2) }}</td>
                            <td class="text-center">
                                @if ($inventory->product_image)
                                    <a class="avatar avatar-xs" target="_blanck" href="{{ $inventory->product_image }}">
                                        <img src="{{ $inventory->product_image }}" class="avatar-img rounded-circle"
                                            alt="..."></a>
                                @endif
                            </td>
                            <td>
                                <a href="#" data-toggle="modal" data-target="#exampleModal11"
                                    data-id="{{ $inventory->id }}" id="eInventory"
                                    class="btn btn-success-soft btn-sm mr-1"><i class="far fa-eye"></i></a>
                                {{-- <a href="#" class="btn btn-danger-soft btn-sm"><i class="far fa-trash-alt"></i></a> --}}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- create new inventory --}}
        <div wire:ignore.self class="modal fade my-modal" id="exampleModal1"  tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel4" aria-hidden="true" >
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Add New Inventory</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="close-modal-button" wire:click="closeModal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form wire:submit.prevent="save" wire:loading.class="opacity-50" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group" wire:ignore>
                                <label for="exampleInputEmail1" class="font-weight-600">Choose Vendor</label>
                                <select  wire:model.defer="vendor_id" wire:ignore class="form-control form-control-lg mt-2 " required>
                                    <option selected value="">Choose Vendor</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">
                                            {{ $vendor->first_name . ' ' . $vendor->last_name }} </option>
                                    @endforeach
                                </select>
                                @error('vendor_id')
                                    <span style="color: rgba(255, 0, 0, 0.438)" class="error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="font-weight-600">Product Name</label>
                                <input type="text" wire:model.defer="product_name"  required class="form-control"
                                    id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter product name">
                                    @error('product_name')
                                    <span style="color: rgba(255, 0, 0, 0.438)" class="error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="font-weight-600">Product Price</label>
                                <input type="number" class="form-control" required wire:model.defer="product_price" 
                                    id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter product price">
                                    @error('product_price')
                                    <span style="color: rgba(255, 0, 0, 0.438)" class="error">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="font-weight-600">Product Image </label>
                                <input type="file" name="image" accept="image/*" wire:model="image"  class="form-control"
                                    id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Choose product image">
                                @error('image')
                                    <span style="color: rgba(255, 0, 0, 0.438)" class="error">{{ $message }}</span>
                                @enderror
                                @if ($image)
                                <div class="align-center p-1">
                                    <a href="#" class="profile-image">
                                        <img src="{{ $image->temporaryUrl() }}" class="avatar avatar-xl rounded-circle img-border height-100" alt="Card image">
                                    </a>
                                </div>
                                    {{-- <img src="{{ $image->temporaryUrl() }}" class="img-thumbnail text-center"> --}}
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="font-weight-600">Description</label>
                                <textarea rows="4" cols="80" name="description" wire:model.defer="description" class="form-control"
                                    placeholder="Here can be your product description"></textarea>
                                    @error('description')
                                    <span style="color: rgba(255, 0, 0, 0.438)" class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" wire:click="closeModal" class="btn btn-danger " id="my-button" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
       
        {{-- edit inventory  --}}
        <div wire:ignore.self class="modal fade" id="exampleModal11" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-600 VHead" id="exampleModalLabel4 VHead"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form wire:submit.prevent="update" wire:loading.class="opacity-50" method="post"
                        enctype="multipart/form-data" wire:ignore>
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="vid" id="VId" wire:model.defer="vid">
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="font-weight-600">Choose Vendor</label>
                                <select class="form-control form-control-lg mt-2  VendorId" wire:model.defer="vendor_id" id="VenId"
                                    name="vendor_id" required>
                                    <option value="">Choose Vendor</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">
                                            {{ $vendor->first_name . ' ' . $vendor->last_name }} </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="font-weight-600">Product Name</label>
                                <input type="text" name="product_name" wire:model.defer="product_name" required class="form-control VPn"
                                    id="exampleInputEmail1 VPn" aria-describedby="emailHelp"
                                    placeholder="Enter product name">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="font-weight-600">Product Price</label>
                                <input type="number" class="form-control VPp" wire:model.defer="product_price" required name="product_price"
                                    id="VPp exampleInputEmail1" aria-describedby="emailHelp"
                                    placeholder="Enter product price">
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="font-weight-600">Product Image </label>
                                <input type="file" name="product_image" wire:model.defer="product_image" wire:model="product_image" accept="image/*" class="form-control"
                                    id="exampleInputEmail1" aria-describedby="emailHelp"
                                    placeholder="Choose product image">
                                @error('product_image')
                                    <span style="color: rgba(255, 0, 0, 0.438)" class="error">{{ $message }}</span>
                                @enderror
                                @if ($product_image)
                                <div class="align-center p-1">
                                    <a href="#" class="profile-image">
                                        <img src="{{ $product_image->temporaryUrl() }}" class="avatar avatar-xl rounded-circle img-border height-100" alt="Card image">
                                    </a>
                                </div>
                                    {{-- <img src="{{ $image->temporaryUrl() }}" class="img-thumbnail text-center"> --}}
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="font-weight-600">Description</label>
                                <textarea rows="4" cols="80" wire:model.defer="description" id="Vdesc" name="description" class="form-control"
                                    placeholder="Here can be your product description"></textarea>
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
        {{-- @php
            dd($showModal);
        @endphp --}}
<div>
    <div class="modal fade" id="exampleModal1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true" wire:ignore>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Add New Inventory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form wire:submit.prevent="save" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Choose Vendor</label>
                            <select class="form-control form-control-lg mt-2 basic-single"  name="vendor_id" required >
                                    <option selected value="">Choose Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{$vendor->id}}">{{$vendor->first_name . ' ' . $vendor->last_name}} </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Product Name</label>
                            <input type="text" name="product_name"  required class="form-control" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter product name">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Product Price</label>
                            <input type="number" class="form-control" required  name="product_price" id="exampleInputEmail1"
                                aria-describedby="emailHelp" placeholder="Enter product price">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Product Image </label>
                            <input type="file" wire:model="file" wire.change="preview">
                            @if ($preview)
                                <img src="{{ $preview }}">
                            @endif
                            
                                {{-- <div wire:loading wire:target="product_image">Uploading image...</div> --}}
                                
                        </div>
                       
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="font-weight-600">Description</label>
                            <textarea rows="4" cols="80" name="description" wire:model="description" class="form-control" placeholder="Here can be your product description"></textarea>
                            @error('description') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger " data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Save Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


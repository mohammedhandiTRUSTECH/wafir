<div class="row" id="{{$rand}}">
    <div class="col-md-3">
        <div class="card-body">
            <h6 class="card-subtitle">Sales Percentage:</h6>
            <div class="form-group mb-0">
                <input type="number" min="1" max="100" value="{{old('sales_percentage_'.$rand)}}" class="form-control"
                       name="sales_percentage_{{$rand}}"
                       id="inputText">
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card-body">
            <h6 class="card-subtitle">Commission Percentage:</h6>
            <div class="form-group mb-0">
                <input type="number" min="0.0" step="0.01" max="100" value="{{old('commission_percentage_'.$rand)}}" class="form-control"
                       name="commission_percentage_{{$rand}}"
                       id="inputText">
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-body">
            <h6 class="card-subtitle"><label></label></h6>
            <div class="form-group mb-0" >
                <button type="button" onclick=" return removeTarget('{{$rand}}')" class="btn btn-danger"><i class="la la-trash la-2x"></i></button>
            </div>
        </div>
    </div>
</div>

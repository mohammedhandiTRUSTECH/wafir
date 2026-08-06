@include('layouts.header',['breadcrumb' => ['Import Users Sales'], 'pageTitle' => 'Import Users Sales','title' => 'Import Users Sales'])

<!-- Start Contentbar -->
<div class="contentbar">
    <div class="col-lg-12">
        <div class="col-md-12">
            @if ($errors->any())
                <br>
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->getMessages() as $key => $messages)
                            <strong>{{ $key }}</strong>
                            <ul>
                                @foreach ($messages as $msg)
                                    <li>{{ $msg }}</li>
                                @endforeach
                            </ul>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session()->has('users-imported'))
                <div class="alert alert-success">
                    {{session()->get('users-imported')}}
                </div>
            @endif

            <form method="post" action="{{route('users.import')}}" enctype="multipart/form-data">
                @csrf
                <div class="card m-b-30">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card-body">
                                <h6 class="card-subtitle">File:</h6>
                                <div class="form-group mb-0">
                                    <input type="file" name="file"
                                           id="inputText">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ asset('Import-Sales-Template.xlsx')}}">Download Template</a>
                        </div>
                    </div>

                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>

        </div>
    </div>
</div>

@include('layouts.footer')

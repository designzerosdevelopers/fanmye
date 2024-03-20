<div class="pb-2">
    <div class="pb-2 text-center">{{__('You can see value post-unlock, tips, subscription.')}}</div>
    <div class="">
        <div class="row">
            <div class="col py-3 text-bold border-bottom">
                <div class="col-lg-12 text-truncate d-md-block text-center">{{__('Your statistics list')}}</div>
            </div>
        </div>
        <form method="post" action="{{route('statistics.period')}}" class="mt-4">
            @csrf
            <!-- Form for Date Inputs -->
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="form-group text-center">
                            <label for="start" class="form-label">{{__('Start Date:')}}</label>
                            <input type="date" name="start" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group text-center">
                            <label for="end" class="form-label">{{__('End Date:')}}</label>
                            <input type="date" name="end" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary">{{__('Submit')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <div class="table-responsive">
            <div class="table-wrapper mx-5">
                <div>
                    <!-- Table Header -->
                    <!-- Table Body -->
                    <div class="row">
                        <div class="col text-xs "><b>Message unlock</b></div>
                        <div class="col text-xs "><b>Post unlock</b></div>
                        <div class="col text-xs "><b>Subscribers</b></div>
                        <div class="col text-xs "><b>Tips</b></div>
                        <div class="col text-xs "><b>Total</b></div>
                    </div>

                    <!-- Statistics Values -->
                    <div class="row">
                            @foreach($statistics as $value)
                                <div class="col"><p>{{ $value }}</p></div>
                            @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
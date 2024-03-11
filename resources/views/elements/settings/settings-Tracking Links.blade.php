<div class="pb-2">
    {{-- <div class="pb-2 text-center">{{__('How to use Tracking Links.')}}</div> --}}
        <div class="pl-5 pr-5">
            @if(empty($linkdata))
            <form method="POST" action="{{route('link.shorten')}}">
                @csrf
                <div class="form-group">
                    <label for="url_title">{{__('Title')}}</label>
                    <input class="form-control" id="url_title" name="title" type="text" required>
                </div>
            
                <div class="form-group">
                    <label for="original_url">{{__('Orignal URL')}}</label>
                    <input class="form-control" id="original_url" name="original_url" type="text" required>
                </div>

                <button class="btn btn-primary btn-block rounded mr-0" type="submit">{{__('Generate')}}</button>
            </form>
            @else
            <form method="POST" action="{{ route('link.update', $linkdata->id) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="url_title">{{ __('Title') }}</label>
                    <input class="form-control" id="url_title" name="title" type="text" value="{{ $linkdata->title }}" required>
                </div>
            
                
                <div class="form-group">
                    <label for="original_url">{{ __('Destination URL') }}</label>
                    <input class="form-control" id="original_url" name="original_url" type="text" value="{{ $linkdata->original_url }}" required>
                </div>
                
                <button class="btn btn-primary btn-block rounded mr-0" type="submit">{{ __('Save') }}</button>
            </form>
            @endif
        </div>
    </div>
</div>
@if(!empty($generatelinks))
<div class="table-wrapper mx-5">
    <div>
        <div class="col py-3 text-bold border-bottom">
            <div class="col-lg-12 text-truncate d-md-block text-center">{{__('Tracking Links')}}</div>
        </div>

        <!-- Table Header -->
        <div class="row">
            <div class="col"><b>Title</b></div>
            <div class="col"><b>Clicks</b></div>
            <div class="col"><b>Sign Ups</b></div>
            <div class="col"><b>Subscribers</b></div>
            <div class="col"><b>Options</b></div> <!-- Add a column for Actions -->
        </div>

        <!-- Table Body -->
        @if(count($generatelinks))
            @foreach($generatelinks as $link)
                <div class="row">
                    <div class="col">{{$link->title}}</div>
                    <div class="col">{{$link->visitor}}</div>
                    <div class="col">{{$link->sign_up}}</div>
                    <div class="col">{{$link->subscriber}}</div>
                    <div class="col">
                        <!-- Dropdown for Actions -->
                        <div class="dropdown">
                            <span class="dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <b>  &hellip; </b>
                            </span>
                            
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <!-- Copy Button -->
                                <button class="dropdown-item" type="button" onclick="copyToClipboard('{{ $link->shorten_url }}')">
                                    {{__('Copy link')}}
                                </button>
                                <!-- Edit Button -->
                                <a class="dropdown-item" href="{{ route('link.edit', $link->id) }}">
                                    {{__('Edit')}}
                                </a>
                                <!-- Delete Button -->
                                <form action="{{ route('link.distory', $link->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item" onclick="return confirm('Are you sure you want to delete this link?')">
                                        {{__('Delete')}}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="d-flex flex-row-reverse mt-3 mr-4">
                {{ $generatelinks->onEachSide(1)->links() }}
            </div>
        @else
            <div class="p-3 text-center">
                <p>{{__('There are no tracking links to show.')}}</p>
            </div>
        @endif
    </div>
</div>

@endif



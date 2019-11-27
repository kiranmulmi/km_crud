@section('top_action')
    @if(!empty($formConfig['actions']))
        @foreach($formConfig['actions'] as $action => $status)
            @if($status == true && $action == 'delete' && \App\Libraries\Permission::check($model_name.'-delete'))
                <a class="btn btn-danger"
                   onclick="return confirm('Are you sure want to delete?')"
                   href="{{ url($model_name . '/delete/' . $data_model->id) }}">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </a>
            @endif
            @if($status == true && $action == 'edit' && \App\Libraries\Permission::check($model_name.'-edit'))
                <a class="btn btn-primary"
                   href="{{ url($model_name . '/edit/' . $data_model->id) }}">
                    <i class="fa fa-edit" aria-hidden="true"></i>
                </a>
            @endif
            @if($action == 'html')
                {!! $status !!}
            @endif
        @endforeach
    @endif
@stop


@if(!empty($view['top_bar_image']))
    <div class="col col-md-2">
        <img src="{{ $view['top_bar_image']['uri'] }}" class="img-lg" alt="{{ $view['top_bar_image']['value'] }}"/>
    </div>
@endif

<div class="col col-md-10">
    @if(!empty($view['top_bar_title']))
        <div style="font-size: 30px">
            {{ $view['top_bar_title']['value'] }}
        </div>
    @endif

    @if(!empty($view['top_bar_description']))
        @foreach($view['top_bar_description'] as $item)
            <div class="small">{{ $item['value'] }}</div>
        @endforeach
    @endif

    @if(!empty($view['top_bar_phase']))
        <div>
            <div class="container-fluid">
                <br/><br/>
                <ul class="list-unstyled km-multi-steps">
                    @foreach($view['top_bar_phase']['data'] as $item)
                        <li class="{{ $item['activeClass'] }}">{{ $item['value'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="text-muted" style="padding-top: 20px">
        <i class="fa fa-clock-o"></i> {{ __('created') }}
        : {{ $data_model->created_at->format('d/m/Y h:i') }}
        &nbsp;&nbsp;<i class="fa fa-clock-o"></i> {{ __('updated') }}
        : {{ $data_model->updated_at->format('d/m/Y h:i') }}
    </div>
</div>


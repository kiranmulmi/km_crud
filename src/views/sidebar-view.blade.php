<div class="panel">
    <div class="panel-body">

        {{-- sidebar button s--}}
        @if(isset($formConfig['detailPage']['buttons']))
            @foreach($formConfig['detailPage']['buttons'] as $item)
                @php
                    $label = $item['label'];
                    $link = $item['link'];
                    $btnClass = isset($item['btnClass']) ? $item['btnClass'] : 'btn btn-info';
                    $popup = isset($item['popup']) && $item['popup'] == true ? 'onclick="showCommonPopup(\'' . $link . '\')"' : '';
                    $href = !empty($popup) ? 'javascript:void(0)' : $link;
                    $perm = isset($item['permission']) ? $item['permission'] : true;
                @endphp

                @if ($perm)
                    <div>
                        <a href="{{ $href }}"
                           class="{{ $btnClass }} btn-block" {!! $popup !!} >
                            {{ $label }}
                        </a>
                    </div>
                @endif

            @endforeach
        @endif

        {{-- Relation sidebar--}}
        @if(isset($formConfig['detailPage']['relationAddButton']))
            @foreach($formConfig['detailPage']['relationAddButton'] as $subModelName => $subValue)
                <div>
                    <a href="{{ url($subModelName . '/create?' . $model_name . '_id=' . $data_model->id . '&destination=' . Request::path()) }}"
                       class="btn btn-info btn-block">
                        {{ $subValue['label'] }}
                    </a>
                </div>
            @endforeach
        @endif

        {{-- Main sidebar --}}
        @if(!empty($view['sidebar']))
            @foreach($view['sidebar']  as $item)
                @if(!empty($item['data']))
                    <div>
                        <h4>{{ $item['label'] }}</h4>
                        <ul>
                            @foreach($item['data'] as $subItem)
                                @if(!empty($subItem['link']))
                                    <li><a href="{{ $subItem['link'] }}"> {!! $subItem['value']  !!}</a></li>
                                @else
                                    <li>{!! $subItem['value']  !!}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</div>


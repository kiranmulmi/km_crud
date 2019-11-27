@extends('admin.admin-master')
@section('breadcrumb')
    {!! kmRenderBreadCrumb(['#' => __('Home'), 'nolink' => $formConfig['label'] ]) !!}
@stop
@section('page_title')
    {{ $formConfig['label'] }}
@stop
<?php $action_html = kmGetIndexPageActionButtonsHtml($model_name, $formConfig)?>
@section('top_action')
    <?php
    $showAddButton = true;
    if (isset($formConfig['actions']['add']) && $formConfig['actions']['add'] == false) {
        $showAddButton = false;
    }
    ?>
    @if($showAddButton)
        {!!  kmGetIndexPageAddAction($model_name)  !!}
    @endif
@stop

@section('content')

    {{--Filter Section--}}

    @php($showFilter = isset($formConfig['tableViewFilter']['hidden']) && $formConfig['tableViewFilter']['hidden'] == true ? false : true )
    @if($showFilter)
        <div class="search-section">
            <div class="row">
                <?php $loopCount = 0;?>
                @foreach ($formConfig['attributes'] as $name => $config)
                    <?php $defaultSearch = isset($config['defaultSearch']) ? $config['defaultSearch'] : ''?>
                    @if(isset($config['search']) && $config['search'] == TRUE)
                        @if($loopCount == 0)
                            <div class="col-md-12">
                                <h4>{{ __('Filters') }}</h4>
                            </div>
                            <div class="clearfix"></div>
                            <?php $loopCount++ ?>
                        @endif
                        <div class="col col-md-3">
                            <div class="form-group">
                                <label>{{ $config['label'] }}</label>
                                @if($config['type'] == 'text')
                                    <input type="text" class="form-control" id="crud-search-{{ $name }}"
                                           onkeyup="kmTableSearch()" value="{{ $defaultSearch }}"/>
                                @endif

                                @if($config['type'] == 'modelSelect')
                                    <?php
                                    $modelSelectModelAttr = $config['relationship'];
                                    $allData = $modelSelectModelAttr['model']::all();
                                    ?>
                                    <select class="form-control crud-select2" multiple="" onchange="kmTableSearch()"
                                            id="crud-search-{{ $name }}">
                                        <option>{{ __('Select') }}</option>
                                        @foreach($allData as $item)
                                            <option value="{{ $item->{$modelSelectModelAttr['key']} }}"
                                                    @if($defaultSearch == $item->{$modelSelectModelAttr['key']}) selected @endif >{{ $item->{$modelSelectModelAttr['value']} }}</option>
                                        @endforeach
                                    </select>
                                @endif

                                @if($config['type'] == 'entitySelect')
                                    <?php $allData = $config['model']::all(); ?>
                                    <select class="form-control crud-select2" multiple="" onchange="kmTableSearch()"
                                            id="crud-search-{{ $name }}">
                                        <option>{{ __('Select') }}</option>
                                        @foreach($allData as $item)
                                            <option value="{{ $item->id }}"
                                                    @if($item->id == $defaultSearch) selected @endif>{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                @endif

                                @if($config['type'] == 'customList_1')
                                    <?php
                                    //$modelSelectModelAttr = $config['relationship'];
                                    //$allData = $modelSelectModelAttr['model']::all();

                                    $allData = $config['options']['data']();
                                    $key = $config['options']['key'];
                                    $value = $config['options']['value'];

                                    ?>
                                    <select class="form-control crud-select2" multiple="" onchange="kmTableSearch()"
                                            id="crud-search-{{ $name }}">
                                        <option>{{ __('Select') }}</option>
                                        @foreach($allData as $item)
                                            <option value="{{ $item->{$key} }}"
                                                    @if($defaultSearch == $item->{$key}) selected @endif >{{ $item->{$value} }}</option>
                                        @endforeach
                                    </select>
                                @endif

                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
    {{-- filter section ends --}}

    <div style="overflow-y: auto">
        <div><h4>{{ ucfirst($model_name) }} (<span id="crud-table-count-{{ $model_name }}">0</span>)</h4></div>
        <table class="table" id="km-table-view-{{ $model_name }}">
            <thead>
            <tr>
                @foreach($formConfig['attributes'] as $name => $config)
                    <?php $width = isset($config['tableView']['width']) ? 'style="width:' . $config['tableView']['width'] . '"' : '' ?>
                    @if(isset($config['tableView']['view']) && $config['tableView']['view'] == FALSE)
                        @continue
                    @endif
                    <th {!! $width !!}>{{ __($config['label']) }}</th>
                @endforeach
                @if(!empty($action_html))
                    <th>{{ __('Action') }}</th>
                @endif
            </tr>
            </thead>
            <tbody>
            <tr>
                @foreach($formConfig['attributes'] as $name => $config)
                    @if(isset($config['tableView']['view']) && $config['tableView']['view'] == FALSE)
                        @continue
                    @endif
                    <?php $width = isset($config['tableView']['width']) ? 'style="width:' . $config['tableView']['width'] . '"' : '' ?>
                    <td {!! $width !!}>
                        @if(isset($config['link']) && !empty($config['link']))
                            {{--TODO: remove this logic in future --}}
                            <a href="{{ $config['link'] }}">{{ '{'.$name.'}' }}</a>
                        @elseif(isset($config['tableView']['link']) && !empty($config['tableView']['link']))
                            <a href="{{ $config['tableView']['link'] }}">{{ '{'.$name.'}' }}</a>
                        @else
                            {{ '{'.$name.'}' }}
                        @endif
                    </td>
                @endforeach
                @if(!empty($action_html))
                    <td> {!! $action_html !!} </td>
                @endif
            </tr>
            </tbody>
        </table>
        <div class="crud-pagination"></div>
    </div>
@stop

@section('script')
    <script>
        var kmTableGrid = function () {
            var search = {};
            @foreach ($formConfig['attributes'] as $name => $config)
                @if(isset($config['search']) && $config['search'] == TRUE)
                search['{{$name}}'] = $("#crud-search-{{ $name }}").val();
            @endif
                @if(isset($config['tableView']['default']))
                search['{{$name}}'] = '{{ $config['tableView']['default'] }}';
            @endif
            @endforeach

            $('#km-table-view-{{ $model_name }}').ajaxGrid({
                limit: 6,
                url: '{{ url($model_name.'/list-all-json') }}',
                columns: [
                    {
                        mRender: function (row) {
                            var a = {};
                            @foreach($formConfig['attributes'] as $name => $config)
                                a['{{$name}}'] = row['{{$name}}'];
                            @endforeach
                                a['id'] = row.id;
                            return a;
                        }
                    }
                ],
                filter: search,
                previous: "&larr;",
                next: "&rarr;",
                table: true,
                paginationWrap: '.crud-pagination',
                callback: function (response) {
                    $('#crud-table-count-{{ $model_name }}').text(response.count)
                }

            });
        };
        kmTableGrid();

        var kmTableSearch = function () {
            var search = {};
            @foreach ($formConfig['attributes'] as $name => $config)
                @if(isset($config['search']) && $config['search'] == TRUE)
                search['{{$name}}'] = $("#crud-search-{{ $name }}").val();
            @endif
            @endforeach
            $('#km-table-view-{{ $model_name }}').trigger('refreshGrid', search);
        };
    </script>
@stop

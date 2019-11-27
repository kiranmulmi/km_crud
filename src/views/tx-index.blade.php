@extends('admin.admin-master')
@section('breadcrumb')
    {!! kmRenderBreadCrumb(['#' => __('Home'), 'nolink' => $category_type ]) !!}
@stop
@section('page_title')
    {{ $taxonomyConfiguration['label'] }}
@stop
@section('top_action')
    <div><a href="{{url('taxonomy/' . $category_type . '/create') }}"
            class="btn btn-primary pull-right">{{ __('Add') }}</a></div>
@stop

@section('content')
    <div>
        <table class="table table-striped" id="km-table-view-{{ $category_type }}">
            <thead>
            <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Parent') }}</th>
                <th>{{ __('Action') }}</th>
            </tr>
            </thead>
            <tbody>
            <tr class="km-row-sortable" row-id="{id}" row-data="{weight}">
                <td>{name}</td>
                <td>{parent}</td>
                <td>{action}</td>
            </tr>
            </tbody>
        </table>
        <div class="crud-pagination"></div>
    </div>
@stop

@section('script')
    <script>
        var kmTaxanomyTableGrid = function () {
            $('#km-table-view-{{ $category_type }}').ajaxGrid({
                limit: 6,
                url: '{{ url('taxonomy/'.$category_type.'/list-all-json') }}',
                columns: [
                    {
                        mRender: function (row) {
                            var a = {};
                            a['id'] = row.id;
                            a['name'] = row.name;
                            a['parent'] = row.parent;
                            a['weight'] = row.weight;
                            var edit = '<a class="btn btn-primary" href="{{ url('taxonomy/'.$category_type.'/edit') }}/' + row.id + '">{{ __('Edit') }}</a>';
                            var del = '<a onclick="return confirm(\'Are you sure want to delete this?\')" class="btn btn-danger" href="{{ url('taxonomy/'.$category_type.'/delete') }}/' + row.id + '">{{ __('Delete') }}</a>';
                            a['action'] = edit + ' ' + del;
                            return a;
                        }
                    }
                ],
                filter: {
                    'id': '{{ request()->get('id') }}',
                },
                previous: "&larr;",
                next: "&rarr;",
                table: true,
                paginationWrap: '.crud-pagination',
                rowSortable:function (data) {
                    $.ajax({
                        url: APP_URL+'/taxonomy/weight/update',
                        type: 'post',
                        data: {list: data},
                        success: function () {
                            //console.log('success');
                            $('#km-table-view-{{ $category_type }}').trigger('refreshGrid',{});
                        }
                    })
                },
                rowSortableClass:'.km-row-sortable',
                callback: function (response) {

                }

            });
        };
        kmTaxanomyTableGrid();
    </script>
@stop

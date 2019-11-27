@php($uniqueKey = 'details')
<div class="panel">
    <div id="tabs-{{ $uniqueKey }}" class="tabs-min">
        <ul>
            <li><h4><a href="#tab_1-{{$uniqueKey}}">{{ __('Details') }}</a></h4></li>
            @if(isset($formConfig['detailPage']['relationTab']))
                @php($mloop = 1)
                @foreach($formConfig['detailPage']['relationTab'] as $itemK => $itemV)
                    <li>
                        <h4>
                            <a href="#tab_{{ $itemK }}-{{$uniqueKey}}">
                                {{ $itemV['label'] }}
                                (<span id="km-rel-table-view-{{ $itemK }}-{{ $uniqueKey }}-{{ $mloop }}-count">0</span>)
                            </a>
                        </h4>
                    </li>
                    @php($mloop++)
                @endforeach
            @endif
            @if(isset($formConfig['detailPage']['extraTab']))
                @php($extTabCount = 20)
                @foreach($formConfig['detailPage']['extraTab'] as $extTab)
                    <li><h4><a href="#tab-{{$extTabCount}}">{{ $extTab['label'] }}</a></h4></li>
                    @php($extTabCount++)
                @endforeach
            @endif
            <li><h4><a href="#tab_2-{{$uniqueKey}}">{{ __('Activity') }}</a></h4></li>
        </ul>

        <div id="tab_1-{{$uniqueKey}}" class="tab-container">
            <div class="row">
                <div class="col-md-10 ck-content">

                    {{-- Main Detail --}}
                    @if(!empty($view['detail_section']))
                        @foreach($view['detail_section'] as $item)
                            <div class="row">
                                <h4>{{ $item['label'] }}</h4>
                                <p>{!! nl2br($item['value'])  !!}</p>
                            </div>
                        @endforeach
                    @endif

                    {{-- Uploaded References--}}
                    @foreach($formConfig['attributes'] as $name => $attribute)
                        @if(isset($attribute['type']) && $attribute['type'] == 'reference')
                            @php($uploads = kmGetMedia($data_model->id, $model_name, $name, true))
                            <div class="km-uploaded-reference-wrapp">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>{{ __('File') }}</th>
                                        <th>{{ __('Size') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($uploads as $media)
                                        <tr id="row-{{ bin2hex($media->name) }}">
                                            <td>
                                                <a href="{{ $media->uri }}"
                                                   target="_blank">{{ kmGetIconFromExtension($media->name) . ' ' . $media->name }}</a>
                                            </td>
                                            <td>
                                                {{ round($media->size / (1024 * 1024), 2) }}MB
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        <div id="tab_2-{{$uniqueKey}}" class="tab-container">
        @if($activities->count() > 0)
            <!-- Timeline -->
                <!--===================================================-->
                <div class="timeline">

                    <!-- Timeline header -->
                    <div class="timeline-header">
                        <div class="timeline-header-title bg-primary">Now</div>
                    </div>

                    <!--===================================================-->
                    <!-- End Timeline -->

                    @foreach($activities as $item)
                        <?php
                        $message = '';
                        if ($item->type == 'insert') {
                            $message = ' has created the element';
                        }
                        if ($item->type == 'update') {
                            $message = ' has updated the element';
                        }
                        $user = '<a href="' . url('user/view/' . $item->user->id) . '" class="btn-link text-semibold">' . $item->user->name . '</a>';
                        $finalMessage = $user . $message
                        ?>
                        <div class="timeline-entry">
                            <div class="timeline-stat">
                                <div class="timeline-icon"></div>
                                <div class="timeline-time">{{ $item->created_at->format('d/m/Y h:i') }}</div>
                            </div>
                            <div class="timeline-label">
                                <img class="img-xs img-circle" src="{{ Auth::user()->profileImage() }}"
                                     alt="Profile picture">
                                {!!  $user !!} {!! $message !!}
                            </div>
                        </div>
                    @endforeach


                </div>
                <!-- end of user messages -->
            @else
                <p>{{ __('No record found') }}</p>
            @endif
        </div>
        @if(isset($formConfig['detailPage']['relationTab']))
            @php($mloop = 1)
            @foreach($formConfig['detailPage']['relationTab'] as $itemK => $itemV)

                @php($relFormConfig = $itemV['modelConfig']::config())

                <div id="tab_{{ $itemK }}-{{$uniqueKey}}" class="tab-container">
                    <div>
                        {{-- Search Start --}}
                        <div class="search-section">
                            <div class="row">
                                @foreach ($relFormConfig['attributes'] as $filterName => $relConfig)
                                    @if(isset($relConfig['relationSearch']) && $relConfig['relationSearch'] == TRUE)
                                        <div class="col col-md-3">
                                            <div class="form-group">
                                                <label>{{ $relConfig['label'] }}</label>
                                                @if($relConfig['type'] == 'text')
                                                    <input type="text" class="form-control"
                                                           id="crud-search-{{ $filterName }}-{{ $mloop }}"
                                                           onkeyup="kmTableSearch('{{ $itemK }}', '{{ $mloop }}')"/>
                                                @endif

                                                @if($relConfig['type'] == 'modelSelect')
                                                    <?php
                                                    $modelSelectModelAttr = $relConfig['relationship'];
                                                    $allData = $modelSelectModelAttr['model']::all();
                                                    ?>
                                                    <select class="form-control crud-select2" multiple=""
                                                            onchange="kmTableSearch('{{ $itemK }}', '{{ $mloop }}')"
                                                            id="crud-search-{{ $filterName }}-{{ $mloop }}">
                                                        <option>{{ __('Select') }}</option>
                                                        @foreach($allData as $item)
                                                            <option
                                                                value="{{ $item->{$modelSelectModelAttr['key']} }}">{{ $item->{$modelSelectModelAttr['value']} }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif

                                                @if($relConfig['type'] == 'entitySelect')
                                                    <?php
                                                    $allData = $relConfig['model']::all();
                                                    ?>
                                                    <select class="form-control crud-select2" multiple=""
                                                            onchange="kmTableSearch('{{ $itemK }}', '{{ $mloop }}')"
                                                            id="crud-search-{{ $filterName }}-{{ $mloop }}">
                                                        <option>{{ __('Select') }}</option>
                                                        @foreach($allData as $item)
                                                            <option
                                                                value="{{ $item->id }}">{{ $item->name }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif

                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        {{-- Search End --}}
                        <table class="table"
                               id="km-rel-table-view-{{ $itemK }}-{{ $uniqueKey }}-{{ $mloop }}">
                            <thead>
                            <tr>
                                @foreach($relFormConfig['attributes'] as $name => $config)
                                    @if(isset($config['relationTableView']) && $config['relationTableView'] == FALSE)
                                        @continue
                                    @endif
                                    <th>{{ __($config['label']) }}</th>
                                @endforeach
                                <th>{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                @foreach($relFormConfig['attributes'] as $name => $config)
                                    @if(isset($config['relationTableView']) && $config['relationTableView'] == FALSE)
                                        @continue
                                    @endif
                                    <td>
                                        @if(isset($config['link']) && !empty($config['link']))
                                            <a href="{{ $config['link'] }}">{{ '{'.$name.'}' }}</a>
                                        @else
                                            {{ '{'.$name.'}' }}
                                        @endif
                                    </td>
                                @endforeach

                                @if(!empty($relFormConfig['actions']))
                                    <td>
                                        @foreach($relFormConfig['actions'] as $action => $status)

                                            @if($status == true && $action == 'edit' && \App\Libraries\Permission::check($itemK.'-edit'))
                                                <a class="btn btn-default"
                                                   href="{{ url($itemK . '/edit/{id}') }}">
                                                    <i class="fa fa-edit" aria-hidden="true"></i>
                                                </a>
                                            @endif
                                            @if($status == true && $action == 'delete' && \App\Libraries\Permission::check($itemK.'-delete'))
                                                <a onclick="return confirm('Are you sure want to delete?')"
                                                   class="btn btn-danger"
                                                   href="{{ url($itemK . '/delete/{id}') }}">
                                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                                </a>
                                            @endif
                                            @if($status == true && $action == 'view' && \App\Libraries\Permission::check($itemK.'-view'))
                                                <a class="btn btn-success"
                                                   href="{{ url($itemK . '/view/{id}') }}">
                                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                                </a>
                                            @endif
                                            @if($action == 'html')
                                                {!! $status !!}
                                            @endif
                                        @endforeach
                                    </td>
                                @endif
                            </tr>
                            </tbody>
                        </table>
                        @php($mloop++)
                    </div>
                </div>
            @endforeach
        @endif
        @if(isset($formConfig['detailPage']['extraTab']))
            @php($extTabCount = 20)
            @foreach($formConfig['detailPage']['extraTab'] as $extTab)
                <div id="tab-{{$extTabCount}}" class="tab-container">
                    <div class="row">
                        <div class="col-md-10 ck-content" id="extra-tab-{{ $extTabCount }}-content"></div>
                    </div>
                </div>
                @php($extTabCount++)
            @endforeach
        @endif
    </div>
</div>
<?php $tablulrViews = isset($formConfig['detailPage']['tabularViews']) ? $formConfig['detailPage']['tabularViews'] : []; ?>

@foreach($tablulrViews as $tbViewKey => $tbViewLabel)
    @if(!empty($view[$tbViewKey]))
        <div class="panel">
            <div class="panel-body">
                <h4>{{ $tbViewLabel }}</h4>
                <table class="table">
                    @foreach($view[$tbViewKey] as $item)
                        @if(!empty($item['prefix']))
                            <tr>
                                <td colspan="2">{!! $item['prefix']  !!}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>{{ $item['label'] }}</td>
                            <td>{!! nl2br($item['value'])  !!}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif
@endforeach

@if(isset($formConfig['comment']['detailView']) && $formConfig['comment']['detailView'] == true)
    <div class="panel">
        <div class="panel-body">
            <h4>{{ __('Comments') }}</h4>
            <hr/>
            <div id="comments-container"></div>
        </div>
    </div>
@endif

@section('script')
    <script>
        $(function () {
            $("#tabs-{{$uniqueKey}}").tabs({
                beforeActivate: function (event, ui) {
                    window.location.hash = ui.newPanel.select().attr('id');//ui.newPanel.selector;
                }
            });
        });

        $(window).on('hashchange', function () {
            if (!location.hash) {
                $("#tabs-{{$uniqueKey}}").tabs('option', 'active', 0); // activate first tab by default
                return;
            }
            $('#tabs-{{$uniqueKey}} > ul > li > a').each(function (index, a) {
                if ($(a).attr('href') == location.hash) {
                    $('#tabs-{{$uniqueKey}}').tabs('option', 'active', index);
                }
            });
        });

        @if(isset($formConfig['detailPage']['relationTab']))
        function kmTableSearch(itemK, mloop) {
            @php($mloop = 1)
                @foreach($formConfig['detailPage']['relationTab'] as $itemK => $itemV)
                @php($relFormConfig = $itemV['modelConfig']::config())
            if ('{{ $mloop }}' == mloop) {
                var search = {};
                search['module'] = '{{ $itemK }}';
                @foreach ($relFormConfig['attributes'] as $name => $iV)
                    @if(isset($iV['relationSearch']) && $iV['relationSearch'] == true)
                    search['{{$name}}'] = $("#crud-search-{{ $name }}-{{ $mloop }}").val();
                @endif
                @endforeach
                $('#km-rel-table-view-{{ $itemK }}-{{ $uniqueKey }}-{{ $mloop }}').trigger('refreshGrid', search);
            }
            @php($mloop++)
            @endforeach
        }

        @php($mloop = 1)
        @foreach($formConfig['detailPage']['relationTab'] as $itemK => $itemV)
        $('#km-rel-table-view-{{ $itemK }}-{{ $uniqueKey }}-{{ $mloop }}').ajaxGrid({
            limit: 6,
            url: '{{ url($itemK.'/list-all-rel-json') }}',
            columns: [
                {
                    mRender: function (row) {
                        var a = {};
                        @foreach($relFormConfig['attributes'] as $name => $config)
                            a['{{$name}}'] = row['{{$name}}'];
                        @endforeach
                            a['id'] = row.id;
                        return a;
                    }
                }
            ],
            filter: {
                'rel_id': '{{ $data_model->id }}',
                'module': '{{ $itemK }}',
                'relationKey': '{{ $itemV['relationKey'] }}'
            },
            previous: "&larr; Newer",
            next: "Older &rarr;",
            table: true,
            callback: function (response) {
                $('#km-rel-table-view-{{ $itemK }}-{{ $uniqueKey }}-{{ $mloop }}-count').text(response.count)
            }

        });
        @php($mloop++)
        @endforeach
        @endif

        @if(isset($formConfig['detailPage']['extraTab']))
        @php($extTabCount = 20)
        @foreach($formConfig['detailPage']['extraTab'] as $extTab)
        $.ajax({
            url: '{{ $extTab["url"] }}',
            type: 'get',
            data: {relationId: '{{ $data_model->id }}'},
            success: function (response) {
                $('#extra-tab-{{ $extTabCount }}-content').html(response);
            }
        });
        @php($extTabCount++)
        @endforeach
        @endif

        {{--Comments Js--}}
        @if(isset($formConfig['comment']['detailView']) && $formConfig['comment']['detailView'] == true)
        $(function () {
            //var userArray;
            var saveComment = function (data) {

                // Convert pings to human readable format
                $(data.pings).each(function (index, id) {
                    var user = usersArray.filter(function (user) {
                        return user.id == id
                    })[0];
                    data.content = data.content.replace('@' + id, '@' + user.fullname);
                });

                return data;
            };

            $('#comments-container').comments({
                profilePictureURL: 'https://viima-app.s3.amazonaws.com/media/public/defaults/user-icon.png',
                currentUserId: '{{ Auth::user()->id }}',
                roundProfilePictures: true,
                textareaRows: 1,
                enableAttachments: true,
                enableHashtags: true,
                enableUpvoting: false,
                getComments: function (success, error) {
                    $.ajax({
                        url: APP_URL + '/km-comments/get-comment-json',
                        type: 'get',
                        data: {entityId: '{{ $data_model->id }}', entityType: '{{ $model_name }}'},
                        success: function (response) {
                            var commentsArray = response.data;
                            success(commentsArray);
                        }
                    });
                },
                postComment: function (data, success, error) {
                    $.ajax({
                        url: APP_URL + '/km-comments/post-comment',
                        type: 'post',
                        data: {commentData: data, entityId: '{{ $data_model->id }}', entityType: '{{ $model_name }}'},
                        success: function (response) {
                            data.id = response.data.comment_id;
                            success(saveComment(data));
                        }
                    });
                },
                putComment: function (data, success, error) {
                    $.ajax({
                        url: APP_URL + '/km-comments/put-comment',
                        type: 'post',
                        data: {commentData: data},
                        success: function (response) {
                            success(saveComment(data));
                        }
                    });
                },
                deleteComment: function (data, success, error) {
                    $.ajax({
                        url: APP_URL + '/km-comments/delete-comment',
                        type: 'post',
                        data: {commentData: data},
                        success: function (response) {
                            success();
                        }
                    });
                },
                upvoteComment: function (data, success, error) {
                    setTimeout(function () {
                        success(data);
                    }, 500);
                },
                uploadAttachments: function (commentArray, success, error) {
                    var responses = 0;
                    var successfulUploads = [];

                    var serverResponded = function () {
                        responses++;

                        // Check if all requests have finished
                        if (responses == commentArray.length) {

                            // Case: all failed
                            if (successfulUploads.length == 0) {
                                error();

                                // Case: some succeeded
                            } else {
                                success(successfulUploads)
                            }
                        }
                    }

                    $(commentArray).each(function (index, commentJSON) {

                        // Create form data
                        var formData = new FormData();
                        $(Object.keys(commentJSON)).each(function (index, key) {
                            var value = commentJSON[key];
                            if (value) formData.append(key, value);
                        });

                        formData.append('entityId', '{{ $data_model->id }}');
                        formData.append('entityType', '{{ $model_name }}');

                        $.ajax({
                            url: APP_URL + '/km-comments/attachments',
                            type: 'POST',
                            data: formData,
                            cache: false,
                            contentType: false,
                            processData: false,
                            success: function (commentJSON) {

                                commentArray.file_url = commentJSON.data.uri;
                                commentArray.file_mime_type = commentJSON.file_mime_type;
                                commentArray.created_by_current_user = commentJSON.created_by_current_user;
                                commentArray.profile_picture_url = commentJSON.profile_picture_url;
                                commentArray.created = commentJSON.created;
                                commentArray.modified = commentJSON.modified;
                                commentArray.id = commentJSON.id;
                                commentArray.is_new = commentJSON.is_new;

                                successfulUploads.push(commentArray);
                                serverResponded();
                            },
                            error: function (data) {
                                serverResponded();
                            },
                        });
                    });
                },
            });
        });
        @endif

    </script>
@stop


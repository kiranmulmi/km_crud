@extends('admin.admin-master')
<?php $formConfig = $configClass::config(); ?>
@section('page_title')
    {{ $formConfig['label'] }}
@stop
@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ url($modelName.'') }}">{{ $formConfig['label'] }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ ucfirst(Request::segment(2)) }}</li>
        </ol>
    </nav>
@stop
@section('content')
    @php($randomNumber = 'km-form')
    <?php
    $tabs = isset($formConfig['formTabs']) ? $formConfig['formTabs'] : ['tab_1' => __('')];
    $tabsEnabled = isset($formConfig['formTabs']) ? true : false;
    $tabCount = count($tabs);
    $currentTabCount = 1;
    ?>
    <form action="{{url($formAction)}}" method="POST" id="crud-form-{{$modelName}}">
        <div id="tabs-{{ $randomNumber }}" class="tabs-min">
            @if($tabsEnabled === TRUE)
                <ul>
                    @foreach($tabs as $tabKey => $tabLabel)
                        <li><h4><a href="#{{ $tabKey }}-{{$randomNumber}}">{{ $tabLabel }}</a></h4></li>
                    @endforeach
                </ul>
            @endif

            @foreach($tabs as $tabKey => $tabLabel)

                <div id="{{ $tabKey }}-{{$randomNumber}}" class="tab-container" data-tabindex="{{$currentTabCount}}">

                    @foreach($formConfig['attributes'] as $fieldName => $config)

                        @php($currentTab = isset($config['formView']['tab']) ? $config['formView']['tab'] : 'tab_1')
                        @if($currentTab == $tabKey)

                            <?php
                            $defaultValue = isset($config['formView']['default']) ? $config['formView']['default'] : '';
                            $divHidden = (isset($config['formView']['hidden']) && $config['formView']['hidden'] == true) ? 'style="display: none"' : '';
                            $wrapperClass = (isset($config['formView']['wrapperClass'])) ? $config['formView']['wrapperClass'] : 'col col-md-12 col-lg-12 col-xs-12';
                            $required = isset($config['validate']) && !empty('validate') ? '<span class="required">*</span>' : '';
                            if ($dataModel->{$fieldName} == '') {
                                $dataModel->{$fieldName} = $defaultValue;
                            }
                            ?>
                            @if(isset($config['prefix']) && !empty($config['prefix']))
                                {!! $config['prefix'] !!}
                            @endif
                            <div class="{{ $wrapperClass }}">
                                <div class="form-group" {!! $divHidden !!}>
                                    <label for="{{ $fieldName }}"><b>{{ $config['label'] }} {!! $required !!}</b></label>

                                    @if($config['type'] == 'text')
                                        <input type="text"
                                               class="form-control"
                                               name="{{ $fieldName }}" id="{{ $fieldName }}"
                                               value="{{$dataModel->{$fieldName} }}"
                                        />
                                    @endif

                                    @if($config['type'] == 'date')

                                        <?php $inputValue = !empty($dataModel->{$fieldName}) ? date('m/d/Y', strtotime($dataModel->{$fieldName})) : ''?>

                                        <input type="text"
                                               class="form-control @if($config['type'] == 'date') km-input-datepicker @endif"
                                               name="{{ $fieldName }}" id="{{ $fieldName }}"
                                               value="{{$inputValue }}"
                                        />
                                    @endif

                                    @if($config['type'] == 'dateTime')

                                        <?php $inputValue = !empty($dataModel->{$fieldName}) ? date('m/d/Y h:i', strtotime($dataModel->{$fieldName})) : ''?>

                                        <input type="text"
                                               class="form-control @if($config['type'] == 'dateTime') km-input-datetimepicker @endif"
                                               name="{{ $fieldName }}" id="{{ $fieldName }}"
                                               value="{{$inputValue }}"
                                        />
                                    @endif

                                    @if($config['type'] == 'time')

                                        <input type="text"
                                               class="form-control @if($config['type'] == 'time') km-input-timepicker @endif"
                                               name="{{ $fieldName }}" id="{{ $fieldName }}"
                                               value="{{$dataModel->{$fieldName} }}"
                                        />
                                    @endif

                                    @if($config['type'] == 'password')
                                        <input type="password" class="form-control" name="{{ $fieldName }}"
                                               id="{{ $fieldName }}"
                                               value=""
                                        />
                                    @endif

                                    @if($config['type'] == 'range')
                                        <?php $rangeValue = $dataModel->{$fieldName} > 0 ? $dataModel->{$fieldName} : 1 ?>
                                        <input type="range" class="form-control" name="{{ $fieldName }}"
                                               id="{{ $fieldName }}"
                                               value="{{ $rangeValue }}"
                                               min="{{ $config['minRange'] }}"
                                               max="{{ $config['maxRange'] }}"
                                        />
                                        <p>
                                            {{ __('Value') }}: <span
                                                id="{{ $fieldName }}-range-value">{{ $rangeValue > 0 ? $rangeValue : 0 }}</span>
                                        </p>
                                    @endif

                                    @if($config['type'] == 'select')
                                        <select name="{{ $fieldName }}" id="{{ $fieldName }}"
                                                class="form-control">
                                            <option value="">{{ __('Select') }}</option>
                                            @foreach($config['options'] as $opsK => $opsV)
                                                <option value="{{ $opsK }}" @if($opsK == $dataModel->{$fieldName}) selected @endif>{{ $opsV }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @if($config['type'] == 'textarea')
                                        <textarea
                                            class="form-control @if(isset($config['rte']) && !empty($config['rte'])) editor @endif"
                                            rows="8"
                                            name="{{ $fieldName }}"
                                            id="{{ $fieldName }}">{!! $dataModel->{$fieldName}  !!} </textarea>
                                    @endif

                                    @if($config['type'] == 'modelSelect')
                                        <?php
                                        $dropdownModel = $config['relationship']['model'];
                                        $dropdownModelMethod = $config['relationship']['method'];
                                        $dropdownKey = $config['relationship']['key'];
                                        $dropdownValue = $config['relationship']['value'];
                                        $dropdownModelType = $config['relationship']['type'];

                                        $dropDownData = $dropdownModel::all();

                                        $multi = '';
                                        if ($dropdownModelType == 'many_to_many' || $dropdownModelType == 'one_to_one') {
                                            $multi = '[]';
                                        }

                                        ?>
                                        <select class="form-control crud-select2" name="{{ $fieldName.$multi }}"
                                                id="{{ $fieldName }}"
                                                @if($dropdownModelType == 'many_to_many') multiple="multiple" @endif>
                                            <option>{{ __('Select') }}</option>
                                            @foreach($dropDownData as $item)
                                                <option
                                                    value="{{ $item->{$dropdownKey} }}"
                                                    @if($dataModel->$dropdownModelMethod->contains('id', $item->{$dropdownKey}) || $defaultValue == $item->{$dropdownKey}) selected="selected" @endif
                                                >{{ $item->{$dropdownValue} }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @if($config['type'] == 'image')
                                        <?php
                                        $savedData = kmGetMedia($dataModel->id, $modelName, $fieldName);
                                        echo KMHtmlBuilder::kmImage($fieldName, $savedData->name)
                                        ?>
                                    @endif

                                    @if($config['type'] == 'taxonomy')
                                        <?php
                                        $multiple1 = false;
                                        $multi = '';
                                        if (isset($config['multiple']) && $config['multiple'] == true) {
                                            $multiple1 = true;
                                            $multi = '[]';
                                        }
                                        $allCategories = kmGetAllTaxonomy($config['taxonomy_key']);
                                        $savedCategories = kmGetTaxonomyRelations($dataModel->id, $modelName, $fieldName, true);
                                        //dd($savedCategories);
                                        ?>
                                        <select class="form-control @if( $multiple1 == TRUE) crud-select2 @endif"
                                                name="{{ $fieldName.$multi }}"
                                                id="{{ $fieldName }}"
                                                @if($multiple1) multiple="multiple" @endif>
                                            <option value="">{{ __('Select') }}</option>
                                            @foreach($allCategories as $item)
                                                <option
                                                    @if($savedCategories->contains('category_id', $item->id) || $defaultValue == $item->id) selected="selected"
                                                    @endif
                                                    value="{{ $item->id }}"
                                                >{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @if($config['type'] == 'entitySelect')
                                        <?php
                                        $multiple1 = false;
                                        $multi = '';
                                        if (isset($config['multiple']) && $config['multiple'] == true) {
                                            $multiple1 = true;
                                            $multi = '[]';
                                        }
                                        $allRecords = $config['model']::all();
                                        $savedCategories = kmGetGenericRelations($dataModel->id, $modelName, $fieldName);
                                        //dd($savedCategories);
                                        ?>
                                        <select class="form-control @if( $multiple1 == TRUE) crud-select2 @endif"
                                                name="{{ $fieldName.$multi }}"
                                                id="{{ $fieldName }}"
                                                @if($multiple1) multiple="multiple" @endif>
                                            <option value="">{{ __('Select') }}</option>
                                            @foreach($allRecords as $item)
                                                <option
                                                    @if($savedCategories->contains('to_id', $item->id) || $defaultValue == $item->id) selected="selected"
                                                    @endif
                                                    value="{{ $item->id }}"
                                                >{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @if($config['type'] == 'customList_1')
                                        <?php
                                        $multiple1 = false;
                                        $multi = '';
                                        if (isset($config['multiple']) && $config['multiple'] == true) {
                                            $multiple1 = true;
                                            $multi = '[]';
                                        }
                                        $allRecords = $config['options']['data']();
                                        $key = $config['options']['key'];
                                        $value = $config['options']['value'];
                                        $relationMethod = $config['relation']['relationMethod'];
                                        $relationEntityIdKey = $config['relation']['entity_id'];
                                        $relationForeignIdKey = $config['relation']['foreign_key'];
                                        $collection = $dataModel->$relationMethod;
                                        ?>
                                        <select class="form-control @if( $multiple1 == TRUE) crud-select2 @endif"
                                                name="{{ $fieldName.$multi }}"
                                                id="{{ $fieldName }}"
                                                @if($multiple1) multiple="multiple" @endif>
                                            <option value="">{{ __('Select') }}</option>
                                            @foreach($allRecords as $item)
                                                <option value="{{ $item->{$key} }}"
                                                        @if($collection->contains('id', $item->id) || $defaultValue == $item->id) selected="selected"
                                                    @endif >{{ $item->{$value} }}</option>
                                            @endforeach
                                        </select>

                                    @endif

                                    @if($config['type'] == 'customList_2')
                                        <?php

                                        $allRecords = $config['options']['data']();
                                        $key = $config['options']['key'];
                                        $value = $config['options']['value'];
                                        $textSearch = isset($config['formView']['search']) && $config['formView']['search'] == true ? 'demo_select2' : ''
                                        ?>
                                        <select class="form-control {{ $textSearch }}"
                                                name="{{ $fieldName }}"
                                                id="{{ $fieldName }}"
                                        >
                                            <option value="">{{ __('Select') }}</option>
                                            @foreach($allRecords as $item)
                                                <option value="{{ $item->{$key} }}"
                                                        @if($dataModel->{$fieldName} == $item->id || $defaultValue == $item->id) selected="selected"
                                                    @endif >{{ $item->{$value} }}</option>
                                            @endforeach
                                        </select>

                                    @endif

                                    @if($config['type'] == 'reference')
                                        <?php echo kmReferenceUploader($modelName, $fieldName, $dataModel->id) ?>
                                    @endif

                                    @if($config['type'] == 'ajaxContent')
                                        <div id="{{ $fieldName }}-wrap"></div>
                                    @endif

                                    <label id="{{ $fieldName }}-error" class="error"
                                           for="{{ $fieldName }}"></label>
                                </div>
                            </div>
                            @if(isset($config['suffix']) && !empty($config['suffix']))
                                {!! $config['suffix'] !!}
                            @endif
                        @endif
                    @endforeach

                    <div class="clearfix"></div>
                    @if($tabCount > 1)
                        <div
                            style="border-top: 1px solid #d2d2d2;border-bottom: 1px solid #d2d2d2; padding: 5px;">
                            {{--Previous Button --}}
                            @if($tabCount > 1 && $currentTabCount > 1)
                                <a href="javascript:void(0)" class="btn btn-default"
                                   onclick="changeUITab('{{$currentTabCount - 1}}', 'right')"><span
                                        class="fa fa-backward"></span> {{ __('Previous') }}</a>
                            @endif
                            @if($tabCount > 1 && $currentTabCount < $tabCount)
                                <a href="javascript:void(0)" class="btn btn-default pull-right"
                                   onclick="changeUITab('{{$currentTabCount + 1}}', 'left')">{{ __('Next') }}
                                    <span
                                        class="fa fa-forward"></span> </a>
                            @endif
                            <?php $currentTabCount++ ?>
                            <div class="clearfix"></div>
                        </div>
                    @endif
                </div>
            @endforeach
            <div style="padding: 0px 25px 35px 0px">
                {{--<input type="submit" class="btn btn-primary pull-right" value="{{ __('Save') }}"/>--}}
                <button type="submit" class="btn btn-primary pull-right" id="main_submit_button"
                        data-loading-text="<i class='fa fa-spinner fa-spin '></i> {{ __('Saving') }}">{{ __('Save') }}</button>
            </div>
            {!!csrf_field()!!}
        </div>
    </form>
@stop

@section('script')
    <script>

        $(function () {
            @if($tabsEnabled === TRUE)
            $("#tabs-{{$randomNumber}}").tabs({
                //show: { effect: "fadeIn", duration: "slow" }
            });
            @endif
            $('.km-input-datepicker').datepicker();
            $('.km-input-timepicker').timepicker();

            @foreach($formConfig['attributes'] as $fieldName => $config)
                @if($config['type'] === 'range')
                    document.getElementById("{{ $fieldName }}-range-value").innerHTML = document.getElementById("{{ $fieldName }}").value;
                    document.getElementById("{{ $fieldName }}").oninput = function () {
                        document.getElementById("{{ $fieldName }}-range-value").innerHTML = this.value;
                    };
                @endif

                @if($config['type'] === 'ajaxContent')
                $.ajax({
                    url: '{{ $config['contentUrl'] }}',
                    type:'get',
                    success: function (response) {
                        $('#{{$fieldName}}-wrap').html(response);
                    }
                })
                @endif
            @endforeach
        });

        $("#crud-form-{{$modelName}}").submit(function (e) {
            e.preventDefault();

            var $thisButton = $('#main_submit_button');
            $thisButton.button('loading');

            $('.error').text('');
            CKupdate();

            $.ajax({
                url: '{{url($formAction)}}',
                type: 'POST',
                data: $("#crud-form-{{$modelName}}").serialize(),
                dataType: 'json',
                success: function (response) {
                    //$thisButton.button('reset');
                    window.location = response.data.destination;
                },
                error: function (response) {
                    $thisButton.button('reset');
                    var responseText = JSON.parse(response.responseText);
                    var responseData = JSON.parse(responseText.message);
                    var counter = 1;
                    var firstErrorDivID = '';
                    $.each(responseData, function (k, v) {
                        if(counter === 1) {
                            firstErrorDivID = '#' + k + '-error';
                        }
                        $('#' + k + '-error').text(v[0]);
                    });

                    $('.error').each(function () {
                        if ($(this).text() != '') {
                            var tabIndex = $(this).parents('.tab-container').attr('data-tabindex');
                            $("#tabs-{{$randomNumber}}").tabs({
                                'active': tabIndex - 1,
                            });
                            return false;
                        }
                    });

                    $([document.documentElement, document.body]).animate({
                        scrollTop: $(firstErrorDivID).offset().top - 100
                    }, 1000);
                }
            })
        });
        var changeUITab = function (tabIndex, direction) {
            $("#tabs-{{$randomNumber}}").tabs({
                'active': tabIndex - 1,
            }); // switch to tab

            $([document.documentElement, document.body]).animate({
                scrollTop: $("#tabs-{{$randomNumber}}").offset().top - 40
            }, 1000);

            return false;
        };

        let kmReferenceUpload = function (that) {
            //debugger;
            let files = $(that).prop('files');
            kmReferenceUploadAjax(that, files);
        };

        var kmReferenceUploadAjax = function (that, files) {
            $.each(files, function (k, file_data) {
                var form_data = new FormData();
                form_data.append('file', file_data);
                $.ajax({
                    url: APP_URL + '/km_reference/km_reference_upload_handler',
                    dataType: 'text',
                    contentType: false,
                    processData: false,
                    data: form_data,
                    type: 'post',
                    success: function (response) {
                        var result = JSON.parse(response);
                        kmReferenceUploadRenderPreview(that, result);
                    }
                });
            });
        };

        var kmReferenceUploadRenderPreview = function (that, result) {

            var parent = $(that).parents('.km-generic-reference-block-wrap');
            if (result.status != false) {
                parent.find('.no-reference').remove();
                //var referenceUrl = APP_URL + '/uploads/' + result.fileName;
                parent.append('<input id="input-' + result.hex + '" type="hidden" name="' + $(that).attr('data-fieldName') + '[]" value="' + result.fileName + '" />');

                var referenceTableTbody = parent.find('.km-uploaded-reference-wrap tbody');
                var action = '<a href="javascript:void(0)" class="btn btn-danger" onclick="kmReferenceDelete(\'' + result.hex + '\')"><i class="fa fa-trash" aria-hidden="true"></i></a>';
                referenceTableTbody.append('<tr id="row-' + result.hex + '"><td><a target="_blank" href="' + result.url + '">' + result.icon + ' ' + result.fileName + '</a></td><td>' + result.size + 'MB</td><td>' + action + '</td></tr>');

            } else {
                //parent.find(".km_image_preview").html(result.message);
            }
        };

        var kmReferenceDelete = function (hex) {
            $('#input-' + hex).remove();
            $('#row-' + hex).remove();
        }


        $('.km-upload-area').on('dragenter', function (e) {
            e.stopPropagation();
            e.preventDefault();
        });

        // Drag over
        $('.km-upload-area').on('dragover', function (e) {
            e.stopPropagation();
            e.preventDefault();
            $(this).css('border', '2px solid #028af4');

        });

        // Drag leave
        $('.km-upload-area').on('dragleave', function (e) {
            $(this).css('border', '2px dashed #028af4');

        });

        $('.km-upload-area').on('click', function (e) {
            $(this).parents('.km-generic-reference-block-wrap').find('.km-generic-reference-input').trigger('click');
        });

        $('.km-upload-area').on('drop', function (e) {
            e.stopPropagation();
            e.preventDefault();
            $(this).css('border', '2px dashed #028af4');
            var that = $(this).parents('.km-generic-reference-block-wrap').find('.km-generic-reference-input');
            var files = e.originalEvent.dataTransfer.files;
            kmReferenceUploadAjax(that, files);
            // var fd = new FormData();
            //
            // fd.append('file', file[0]);
            //
            // uploadData(fd);
        });

    </script>
@stop

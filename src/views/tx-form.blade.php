@extends('admin.admin-master')
<?php $randomNumber = Str::random(8)?>
@section('content')
    <form action="{{url($formAction)}}" method="POST" id="crud-form-{{$randomNumber}}">
        <div class="form-group">
            <label for="name">{{ __('Name') }}</label>
            <input type="text" name="name" value="{{ $model->name }}" id="name" class="form-control"/>
            <label id="name-error" class="error" for="name"></label>
        </div>
        <div class="form-group">
            <label for="description">{{ __('Description') }}</label>
            <textarea name="description" id="description" class="form-control">{{ $model->name }}</textarea>
        </div>
        <div class="form-group">
            <label for="parent_id">{{ __('Parent') }}</label>
            <select class="form-control" name="parent_id" id="parent_id">
                <option value="0">{{ __('Select') }}</option>
                @foreach($allTaxonomy as $item)
                    <option value="{{ $item->id }}" @if($model->parent_id == $item->id) selected @endif>{{ $item->name }}</option>
                @endforeach
            </select>
            <label id="parent_id-error" class="error" for="parent_id"></label>
        </div>
        <input type="submit" class="btn btn-primary" value="{{ __('Save') }}">
        {!!csrf_field()!!}
    </form>
@stop

@section('script')
    <script>
        $("#crud-form-{{$randomNumber}}").submit(function (e) {
            e.preventDefault();
            $('.error').text('');

            $.ajax({
                url: '{{url($formAction)}}',
                type: 'POST',
                data: $("#crud-form-{{$randomNumber}}").serialize(),
                dataType: 'json',
                success: function (response) {

                    window.location = '{{ $redirectDestination }}';
                },
                error: function (response) {
                    var responseText = JSON.parse(response.responseText);
                    var responseData = JSON.parse(responseText.message);
                    $.each(responseData, function (k, v) {
                        $('#' + k + '-error').text(v[0]);
                    });
                }
            })
        });
    </script>
@stop

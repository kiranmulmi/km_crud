@extends('admin.admin-master')
<?php $formConfig = $configClass::config();?>
@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ url($model_name.'') }}">{{ $formConfig['label'] }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('Detail') }}</li>
        </ol>
    </nav>
@stop
@section('page_title')
    {{ $formConfig['label'] }} Details
@stop

@php($view = kmGetTopBarElements($model_name, $data_model, $formConfig))

@section('content')
    <div class="row">
        @include('km_crud::top-bar-view')
    </div>
@stop

@section('content2')
    <div class="panel-body" style="padding-top: 0;">
        <div class="row">
            <div class="col-md-9 col-sm-9 col-xs-12">
                @include('km_crud::detail-view')
            </div>
            <div class="col-md-3 col-sm-3 col-xs-12">
                @include('km_crud::sidebar-view')
            </div>
        </div>
    </div>


@stop


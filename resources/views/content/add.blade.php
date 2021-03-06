@extends('global.master')

@section('content')
<div class="row">
    {!! Form::open([
        'action' => 'ContentController@addContent',
        'class' => 'form-horizontal content_add_form'
    ]) !!}
    <input type="hidden" name="type" value="link">

    <p id="currentTab"></p>

        <div class="row" style="margin-bottom: 20px">
            <ul class="nav nav-tabs col-lg-6 offset-lg-3">
                <li class="nav-item">
                    <a class="nav-link active" href="#link" data-toggle="tab">
                        <i class="fa fa-link"></i>
                        @lang('common.add link')
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#content" data-toggle="tab">
                        <i class="fa fa-pencil"></i>
                        @lang('common.add content')
                    </a>
                </li>
            </ul>
        </div>

        @include('global.form.input_value', ['type' => 'text', 'class' => 'group_typeahead', 'name' => 'groupname', 'label' => trans('common.group'), 'value' => Input::get('group')])

        <div id="myTabContent" class="tab-content">
            <div class="tab-pane fade in active" id="link">
                @include('global.form.input_value', ['type' => 'text', 'name' => 'url', 'label' => trans('common.url address'), 'value' => Input::get('url')])
            </div>
            <div class="tab-pane fade" id="content">
                @include('global.form.input', ['type' => 'textarea', 'class' => 'md_editor', 'name' => 'text', 'label' => trans('common.text'), 'rows' => 10])
            </div>
        </div>

    @include('global.form.input_value', ['type' => 'text', 'name' => 'title', 'label' => trans('common.title'), 'maxlength' => '128', 'value' => Input::get('title')])
    @include('global.form.input_value', ['type' => 'textarea', 'name' => 'description', 'label' => trans('common.description'), 'maxlength' => '255', 'value' => Input::get('description')])

    <div class="form-group">
        <label class="col-lg-3 control-label">@lang('common.options')</label>

        <div class="col-lg-4">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('thumbnail', 'on', Input::get('thumbnail') == 'no' ? false : true) !!} @lang('common.thumbnail')
                </label>
            </div>
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('nsfw', 'on', Input::has('18')) !!} @lang('common.nsfw')
                </label>
            </div>
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('eng', 'on', Input::has('eng')) !!} @lang('content.foreign language')
                </label>
            </div>
        </div>

        <div class="col-lg-2">
            <button type="submit" class="btn btn-primary pull-right">
                @lang('content.add content')
            </button>
        </div>
    </div>
    {!! Form::close() !!}
</div>
@stop

@section('scripts')
    <link href="/static/css/simplemde.min.css" rel="stylesheet">
    <script src="/static/js/simplemde.min.js"></script>

    <script>
        var editor = new SimpleMDE($('.md_editor')[0]);
        editor.render();
    </script>
@endsection


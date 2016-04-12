@extends('templates.default')

@section('content')

    <div id="content" class="col-sm-10">
<pre><b>Total Time:</b> {!!$log->execution!!}s

    {!!$log->log!!}
    <b>Total Time:</b> {!!$log->execution!!}s</pre>
    </div>
@stop

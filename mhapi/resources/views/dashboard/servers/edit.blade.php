@extends('templates.default')

@section('content')

    <div id="content" class="col-sm-10">
        <!-- start: Content -->

        <div>
            <hr>
            <ul class="breadcrumb">
                <li><a href="dashboard">Dashboard</a></li>
                <li><a href="">Customer Edit</a></li>
            </ul>
            <hr>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="box">
                    <div class="box-header" data-original-title>
                        <h2>
                            <i class="fa fa-user"></i><span class="break"></span>Server ID {!! $server->server_id !!}
                        </h2>
                        <div class="box-icon">
                            <a href="table.html#" class="btn-minimize"><i class="fa fa-chevron-up"></i></a>
                            <a href="table.html#" class="btn-close"><i class="fa fa-times"></i></a>
                        </div>
                    </div>
                    <div class="box-content">
                        {!! Form::open(array( 'method'=>'post', 'class'=>'form-horizontal')) !!}
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Bounce Server ID:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="groups_in_parallel" name="bounce_server_id" value="{{ $server->bounce_server_id }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Name</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="groups_in_parallel" name="name" value="{{ $server->name }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Hostname</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="groups_in_parallel" name="hostname" value="{{ $server->hostname }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Use For:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="groups_in_parallel" name="use_for" value="{{ $server->use_for }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-default" name="submit" value="1">Submit</button>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div><!--/col-->

        </div><!--/row-->

    </div><!--/content-->

    <hr>
    <!-- page scripts -->


@stop


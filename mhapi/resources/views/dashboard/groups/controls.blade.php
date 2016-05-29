@extends('templates.default')

@section('content')

    <div id="content" class="col-sm-10">
        <!-- start: Content -->

        <div>
            <hr>
            <ul class="breadcrumb">
                <li><a href="dashboard">Dashboard</a></li>
                <li><a href="controls">Group Controls</a></li>
            </ul>
            <hr>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="box">
                    <div class="box-header" data-original-title>
                        <h2><i class="fa fa-user"></i><span class="break"></span>Group Controls</h2>
                        <div class="box-icon">
                            <a href="table.html#" class="btn-minimize"><i class="fa fa-chevron-up"></i></a>
                            <a href="table.html#" class="btn-close"><i class="fa fa-times"></i></a>
                        </div>
                    </div>
                    <div class="box-content">
                        {!! Form::open(array( 'method'=>'post', 'class'=>'form-horizontal')) !!}
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="email">Groups At Once:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="groups_at_once" name="groups_at_once" value="{{ $controls->groups_at_once }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Emails At Once:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="emails_at_once" name="emails_at_once" value="{{ $controls->emails_at_once }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Change Server:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="change_server_at" name="change_server_at" value="{{ $controls->change_server_at }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Compliance Limit:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="compliance_limit" name="compliance_limit" value="{{ $controls->compliance_limit }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Compliance Abuse Range:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="compliance_abuse_range" name="compliance_abuse_range" value="{{ $controls->compliance_abuse_range }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Compliance Unsub Range:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="compliance_unsub_range" name="compliance_unsub_range" value="{{ $controls->compliance_unsub_range }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Compliance Bounce Range:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="compliance_bounce_range" name="compliance_bounce_range" value="{{ $controls->compliance_bounce_range }}">
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


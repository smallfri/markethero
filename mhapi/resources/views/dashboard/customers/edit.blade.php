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
                            <i class="fa fa-user"></i><span class="break"></span>Customer ID {!! $customer->customer_id !!}
                        </h2>
                        <div class="box-icon">
                            <a href="table.html#" class="btn-minimize"><i class="fa fa-chevron-up"></i></a>
                            <a href="table.html#" class="btn-close"><i class="fa fa-times"></i></a>
                        </div>
                    </div>
                    <div class="box-content">
                        {!! Form::open(array( 'method'=>'post', 'class'=>'form-horizontal')) !!}
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">First Name:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="groups_in_parallel" name="first_name" value="{{ $customer->first_name }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Last Name:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="groups_in_parallel" name="last_name" value="{{ $customer->last_name }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Email:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="groups_in_parallel" name="email" value="{{ $customer->email }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="pwd">Status:</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="groups_in_parallel" name="status" value="{{ $customer->status }}">
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


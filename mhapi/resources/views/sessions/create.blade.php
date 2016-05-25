@extends('templates.default')

@section('content')

    <div class="login-box">

        <h2>Login to your account</h2>
        {!! Form::open(['route' => 'login_path','class'=>'sky-form']) !!}
        <fieldset>

            <div class="form-group">
                <div class="controls">
                    <div class="input-group col-sm-12">
                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                        <input type="text" name="email">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="controls">
                    <div class="input-group col-sm-12">
                        <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                        <input type="password" name="password">
                    </div>
                </div>
            </div>

            <div class="button-login">
                <button type="submit" class="btn btn-primary"><i class="icon-off icon-white"></i> Login</button>
            </div>
            <div class="clearfix"></div>
        </fieldset>
        </form>

    </div><!--/col-->


@stop
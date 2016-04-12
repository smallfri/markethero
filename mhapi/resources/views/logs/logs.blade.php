@extends('templates.default')

@section('content')
    <div id="content" class="col-sm-10">

        <div id="pad-wrapper">
            {!!Form::open(array('method'=>'get')) !!}
            <div class="row filter-block">
                <div class="">
                    <label for="level">Level: </label>
                    {!! Form::select('level', $level_array, $level) !!}

                    &nbsp;&nbsp;

                    <label for="time">Min Time: </label>
                    <input id="time" name="time" value="{!! $time !!}"/>

                    &nbsp;&nbsp;

                    <label for="text">Search: </label>
                    <input id="text" name="text" value="{!! $text !!}"/>

                    &nbsp;&nbsp;

                    <button class="btn btn-sm btn-primary" type="submit">Search</button>
                </div>
            </div>

            {!! Form::close() !!}

            <div class="table-wrapper products-table" style="margin-top:20px;">

                <div class="row">
                    <table class="table table-hover">

                        <thead>
                        <tr>
                            <th>#</th>
                            <th>When</th>
                            <th>Execution</th>
                            <th>Level</th>
                            <th>Title</th>

                        </tr>
                        </thead>

                        <tbody>
                        <?php $i = 0; ?>
                        @foreach ($logs as $log)

                            <?php
                            $style = "";
                            if($log->level>=\App\Logger::CRITICAL OR $log->execution>5)
                            {
                                $style = "background:#ff7f7f;";
                            }
                            else
                            {
                                if($log->level>=\App\Logger::ERROR OR $log->execution>3)
                                {
                                    $style = "background:#ffb2b2;";
                                }
                                else
                                {
                                    if($log->level>=\App\Logger::ERROR OR $log->execution>1)
                                    {
                                        $style = "background:#ffe5e5;";
                                    }
                                }
                            }
                            $i++;
                            ?>
                            <tr>
                                <td style="{!!$style!!}">
                                    {!! $i !!}
                                </td>
                                <td style="{!!$style!!}">
                                    {!!\Carbon\Carbon::parse($log->created_at)->diffForHumans()!!}
                                </td>
                                <td style="{!!$style!!}">
                                    {!!$log->execution!!}
                                </td>
                                <td style="{!!$style!!}">
                                    {!!\App\Logger::getLevelName($log->level)!!}
                                </td>
                                <td style="{!!$style!!}">
                                    <a href="logs/viewLog/{!!$log->id!!}" target="_blank">{!!$log->title!!} (#{!!$log->id!!})</a>
                                </td>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@stop
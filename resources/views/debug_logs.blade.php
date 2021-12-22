<!DOCTYPE html>
<html>
    <head>
        <title>Debug Logs List</title>
    </head>
    <body>
        <h2>Debug Logs List</h2>
        <table style="border-collapse: collapse;">
            <thead style="border-bottom:#333 2px solid; text-align:left">
                <tr>
                    <th style="padding:4px 16px 4px 0">Serial</th>
                    <th style="padding:4px 16px 4px 0">Duration</th>
                    <th style="padding:4px 16px 4px 0"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                <tr style="border-bottom:#333 1px solid; {{$log->duration > 3000 ? 'background-color:#ffdddd' : ''}}">
                    <td style="padding:4px 16px 4px 2px">{{$log->serial}}</td>
                    <td style="padding:4px 16px 4px 2px">{{number_format($log->duration, 2)}}</td>
                    <td style="padding:4px 16px 4px 2px"><a href="/debugLogs/{{$log->serial}}/get">view</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>

<!DOCTYPE html>
<html>
    <head>
        <title>Debug List Of Serial #{{$serial}}</title>
    </head>
    <body>
        <h2>Debug List Of Serial #{{$serial}}</h2>
        <table style="border-collapse: collapse;">
            <thead style="border-bottom:#333 2px solid; text-align:left">
                <tr>
                    <th style="padding:4px 16px 4px 0">DateTime</th>
                    <th style="padding:4px 16px 4px 0">Duration</th>
                    <th style="padding:4px 16px 4px 0">Message</th>
                    <th style="padding:4px 16px 4px 0">Context</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $start = 0;
                    $duration = 0;
                @endphp
                @foreach ($logs as $log)
                @php
                    $duration = round($log->microtime - $start, 5) * 1000;
                    $start = $log->microtime;
                @endphp
                <tr style="border-bottom:#333 1px solid;">
                    <td style="padding:4px 16px 4px 2px">{{$log->datetime}}</td>
                    <td style="padding:4px 16px 4px 2px">{{$duration < 1000000 ? number_format($duration,2) : ''}}</td>
                    <td style="padding:4px 16px 4px 2px">{{$log->message}}</td>
                    <td style="padding:4px 16px 4px 2px">{{$log->context}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>

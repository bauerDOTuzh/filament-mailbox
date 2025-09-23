<div style="padding:10px;border:2px solid #f00;background:#fff3f3;color:#900;font-family:Arial;margin-bottom:10px;">
    <strong>[{{ $environment }}] {{ $appName }}</strong>
    <div>Domain: {{ $domain }}</div>
    @if($hasRedirectedRecipients)
        <div>Redirected To: {{ $redirectedTo }}</div>
        <div>Original Recipients: {!! $recipients !!}</div>
    @else
        <div>Recipients: {!! $recipients !!}</div>
    @endif
    <div style="font-size:small;color:#600">Timestamp: {{ $timestamp }}</div>
</div>

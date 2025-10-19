<div style='background-color: #fff; border: 2px solid #e74c3c; border-left: 8px solid #e74c3c; color: #333; padding: 20px; margin-bottom: 25px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); font-family: Arial, sans-serif;'>
    <div style='font-size: 20px; font-weight: 600; margin-bottom: 12px; color: #e74c3c; border-bottom: 1px solid #f4d0cc; padding-bottom: 10px;'>
        {!! __('filament-mailbox::filament-mailbox.banner.test_email', ['appName' => $appName, 'environment' => $environment]) !!}
    </div>
    <div style='font-size: 14px; line-height: 1.6; text-align: left; margin: 15px 0; color: #444;'>
        <table style='border-collapse: collapse; width: 100%; margin-bottom: 10px;'>
            <tr>
                <td style='padding: 5px 15px 5px 0; white-space: nowrap; vertical-align: top;'><strong>{{ __('filament-mailbox::filament-mailbox.banner.environment') }}</strong></td>
                <td style='padding: 5px 0;'>{{ $environment }}</td>
            </tr>
            <tr>
                <td style='padding: 5px 15px 5px 0; white-space: nowrap; vertical-align: top;'><strong>{{ __('filament-mailbox::filament-mailbox.banner.server') }}</strong></td>
                <td style='padding: 5px 0;'>{{ $domain }}</td>
            </tr>
            <tr>
                <td style='padding: 5px 15px 5px 0; white-space: nowrap; vertical-align: top;'><strong>{{ $hasRedirectedRecipients ? __('filament-mailbox::filament-mailbox.banner.original_recipients') : __('filament-mailbox::filament-mailbox.banner.recipients') }}</strong></td>
                <td style='padding: 5px 0;'>{!! $recipients !!}</td>
            </tr>
            @if($hasRedirectedRecipients)
            <tr>
                <td style='padding: 5px 15px 5px 0; white-space: nowrap;'><strong>{{ __('filament-mailbox::filament-mailbox.banner.redirected_to') }}</strong></td>
                <td style='padding: 5px 0;'>{{ $redirectedTo ?? __('filament-mailbox::filament-mailbox.attachments.none') }}</td>
            </tr>
            @endif
        </table>
    </div>
    <div style='font-size: 12px; margin-top: 12px; color: #777; font-style: italic; border-top: 1px solid #f4d0cc; padding-top: 10px;'>
        {{ __('filament-mailbox::filament-mailbox.banner.generated_notice', ['timestamp' => $timestamp]) }}
    </div>
</div>
<?php

return [
    'navigation.group' => 'Logs',

    'navigation.maillog.label' => 'Mail Log',
    'navigation.maillog.plural-label' => 'Mail Logs',

    'table.heading' => 'Mail Logs',

    'column.status' => 'Status',
    'column.subject' => 'Subject',
    'column.to' => 'To',
    'column.from' => 'From',
    'column.cc' => 'CC',
    'column.bcc' => 'BCC',
    'column.message_id' => 'Message ID',
    'column.sent_at' => 'Sent',
    'column.delivered_at' => 'Delivered',
    'column.opened_at' => 'Opened',
    'column.bounced_at' => 'Bounced',
    'column.complaint_at' => 'Complaint',
    'column.body' => 'Body',
    'column.headers' => 'Headers',
    'column.attachments' => 'Attachments',
    'column.data' => 'Data',
    'column.created_at' => 'Created At',
    'column.updated_at' => 'Updated At',
    // Settings navigation
    'navigation.settings.label' => 'Mail Settings',
    'navigation.settings.plural-label' => 'Mail Settings',
    'navigation.settings.current_mail_transport' => 'Current mail transport',
    'navigation.settings.current_mail_remote_ip' => 'Remote IP',
    'navigation.settings.show_environment_banner' => 'Show environment banner',
    'navigation.settings.sandbox_mode' => 'Sandbox mode',
    'navigation.settings.sandbox_address' => 'Sandbox address',
    'navigation.settings.bcc_address' => 'BCC addresses',
    'navigation.settings.allowed_emails' => 'Allowed emails',
    // Actions / modals
    'actions.send_test_email' => 'Send test email',
    'actions.send_test_email_heading' => 'Send test email',
    'actions.send' => 'Send',
    'actions.recipient' => 'Recipient email',
    // Resend action
    'resend_email_heading' => 'Resend email',
    'resend_email_description' => 'Confirm you want to resend this email.',
    'resend_email_success' => 'Mail will be resent in the background',
    'resend_email_error' => 'Failed to resend mail',
    'resend_email_heading' => 'Resend email',
    'to' => 'To',
    'cc' => 'CC',
    'bcc' => 'BCC',
    'insert_multiple_email_placelholder' => 'Enter one or more email addresses',
    'add_attachments' => 'Include attachments',
    'no_attachments_available' => 'No attachments were stored for this email, nothing to include.',
    // Attachment & view labels
    'attachments.download' => 'Download',
    'attachments.none' => 'None',
    'attachments.attachment' => 'attachment',

    // Mail status labels
    'status.bounced' => 'Bounced',
    'status.complained' => 'Complained',
    'status.opened' => 'Opened',
    'status.delivered' => 'Delivered',
    'status.sent' => 'Sent',
    'status.unsent' => 'Unsent',

    // Banner / preview
    'banner.test_email' => '⚠️ TEST EMAIL: :appName - :environment ⚠️',
    'banner.environment' => 'Environment:',
    'banner.server' => 'Server:',
    'banner.recipients' => 'Recipients:',
    'banner.original_recipients' => 'Original Recipients:',
    'banner.redirected_to' => 'Redirected To:',
    'banner.generated_notice' => 'This is not a production email • Generated on :timestamp',
    'banner.unknown_domain' => 'unknown-domain',
    'banner.no_recipients' => 'No recipients found',

    // Preview fallbacks
    'preview.no_html' => 'No HTML content available',
    // Notifications / test email
    'notifications.no_recipient' => 'No recipient available to send test email',
    'notifications.test_email_subject' => 'Filament Mail Log — Test Message',
    'notifications.test_email_body' => 'This is a test email from Filament Mail Log plugin.',
    'notifications.test_sent_title' => 'Test email sent',
    'notifications.test_sent_body' => 'Sent to: :recipient',
    'notifications.test_failed_title' => 'Failed to send test email',
    // Placeholders and hints
    'placeholders.example_email' => 'example@example.com',
    'hints.locked_in_config' => 'This value is locked in config',
    'hints.bcc_help' => 'Separate multiple addresses with commas. Invalid addresses will be ignored.',
    'hints.allowed_help' => 'Separate multiple addresses with commas. Only valid addresses will be used.',
    'hints.using_log_transport' => 'Using log transport - messages are written to the application log.',
    // Stats widget
    'stats.delivered_at' => 'Delivered',
    'stats.opened_at' => 'Opened',
    'stats.clicked_at' => 'Clicked',
    'stats.bounced_at' => 'Bounced',
    'stats.sent_at' => 'Sent',
    'stats.of' => 'of',
    'stats.emails' => 'emails',

    'tabs.all' => 'All',
];

<?php

return [
    'yes' => 'Ano',
    'no' => 'Ne',

    'navigation.group' => 'Záznamy',

    'navigation.maillog.label' => 'Mail Log',
    'navigation.maillog.plural-label' => 'Mail Logy',

    'table.heading' => 'Záznamy e-mailů',

    'column.status' => 'Stav',
    'column.subject' => 'Předmět',
    'column.to' => 'Komu',
    'column.from' => 'Od',
    'column.cc' => 'CC',
    'column.bcc' => 'BCC',
    'column.message_id' => 'ID zprávy',
    'column.sent_at' => 'Odesláno',
    'column.delivered_at' => 'Doručeno',
    'column.opened_at' => 'Otevřeno',
    'column.bounced_at' => 'Odmítnuto',
    'column.complaint_at' => 'Stížnost',
    'column.body' => 'Tělo',
    'column.headers' => 'Hlavičky',
    'column.attachments' => 'Přílohy',
    'column.data' => 'Data',
    'column.created_at' => 'Vytvořeno',
    'column.updated_at' => 'Aktualizováno',
    // Settings navigation
    'navigation.settings.label' => 'Nastavení pošty',
    'navigation.settings.plural-label' => 'Nastavení pošty',
    'navigation.settings.current_mail_transport' => 'Aktuální mailový transport',
    'navigation.settings.current_mail_remote_ip' => 'Vzdálená IP',
    'navigation.settings.show_environment_banner' => 'Zobrazit banner prostředí',
    'navigation.settings.sandbox_mode' => 'Režim sandbox',
    'navigation.settings.sandbox_address' => 'Adresa sandboxu',
    'navigation.settings.bcc_address' => 'BCC adresy',
    'navigation.settings.allowed_emails' => 'Povolené e-maily',
    'navigation.settings.delivery_stats_supported' => 'Podporováno statistiky e-mailu',
    'navigation.settings.track_opens' => 'Sledování otevření e-mailu',
    // Actions / modals
    'actions.send_test_email' => 'Odeslat testovací e-mail',
    'actions.send_test_email_heading' => 'Odeslat testovací e-mail',
    'actions.send' => 'Odeslat',
    'actions.recipient' => 'E-mail příjemce',
    // Resend action
    'resend_email_heading' => 'Znovu odeslat e-mail',
    'resend_email_description' => 'Potvrďte, že chcete tento e-mail znovu odeslat.',
    'resend_email_success' => 'E-mail bude znovu odeslán na pozadí',
    'resend_email_error' => 'Nepodařilo se znovu odeslat e-mail',
    'to' => 'Komu',
    'cc' => 'CC',
    'bcc' => 'BCC',
    'insert_multiple_email_placelholder' => 'Zadejte jednu nebo více e-mailových adres',
    'add_attachments' => 'Přiložit přílohy',
    'no_attachments_available' => 'Pro tento e-mail nebyly uloženy žádné přílohy.',
    // Attachment & view labels
    'attachments.download' => 'Stáhnout',
    'attachments.none' => 'Žádné',
    'attachments.attachment' => 'příloha',

    // Mail status labels
    'status.bounced' => 'Odmítnuto',
    'status.complained' => 'Stížnost',
    'status.opened' => 'Otevřeno',
    'status.delivered' => 'Doručeno',
    'status.sent' => 'Odesláno',
    'status.unsent' => 'Neodesláno',

    // Banner / preview
    'banner.test_email' => '⚠️ TESTOVACÍ E-MAIL: :appName - :environment ⚠️',
    'banner.environment' => 'Prostředí:',
    'banner.server' => 'Server:',
    'banner.recipients' => 'Příjemci:',
    'banner.original_recipients' => 'Původní příjemci:',
    'banner.redirected_to' => 'Přesměrováno na:',
    'banner.generated_notice' => 'Toto není produkční e-mail • Vygenerováno :timestamp',
    'banner.unknown_domain' => 'neznámá-domena',
    'banner.connection' => 'Připojení: :conn',
    // Fallback HTML used when view rendering fails
    'banner.fallback_html' => "<div style='padding:10px;border:2px solid #f00;background:#fff3f3;color:#900;font-family:Arial;'>[:environment] :appName - Sandbox pošty<br/>Příjemci: :recipients<br/>Přesměrováno na: :redirectedTo</div><br/>",
    'banner.no_recipients' => 'Nebyl nalezen žádný příjemce',

    // Preview fallbacks
    'preview.no_html' => 'Žádný HTML obsah není k dispozici',
    // Notifications / test email
    'notifications.no_recipient' => 'Není k dispozici žádný příjemce pro odeslání testovacího e-mailu',
    'notifications.test_email_subject' => 'Filament Mail Log — Testovací zpráva',
    'notifications.test_email_body' => 'Toto je testovací e-mail z Filament Mail Log pluginu.',
    'notifications.test_sent_title' => 'Testovací e-mail odeslán',
    'notifications.test_sent_body' => 'Odesláno na: :recipient',
    'notifications.test_failed_title' => 'Nepodařilo se odeslat testovací e-mail',

    // Placeholders and hints
    'placeholders.example_email' => 'example@example.com',
    'hints.locked_in_config' => 'Tato hodnota je uzamčena v konfiguraci',
    'hints.bcc_help' => 'Oddělte více adres čárkami. Neplatné adresy budou ignorovány.',
    'hints.allowed_help' => 'Oddělte více adres čárkami. Použijí se pouze platné adresy.',
    'hints.using_log_transport' => 'Používá se log transport - zprávy jsou zapisovány do aplikačního logu.',
    // Stats widget
    'stats.delivered_at' => 'Doručeno',
    'stats.opened_at' => 'Otevřeno',
    'stats.clicked_at' => 'Kliknuto',
    'stats.bounced_at' => 'Odmítnuto',
    'stats.sent_at' => 'Odesláno',
    'tabs.all' => 'Vše',
    'stats.of' => 'z',
    'stats.emails' => 'e-mailů',
];

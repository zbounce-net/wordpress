# WordPress 1.2.0

The plugin requires a purchased ZBounce key and explicit consent to external verification. Settings → ZBounce Email Validator controls accepted results across all enabled integrations. Checked results pass; unchecked results reject. The default policy accepts valid, risky/accept-all, unknown and API unavailable. It rejects disposable addresses, invalid syntax, no-mail domains and missing mailboxes. A form's own syntax validation is independent. SMTP uncertainty and an API outage are different results; uncertain/outage results are not cached. Changing policy applies immediately to cached facts without another API charge.

Classic WooCommerce checkout and Checkout Block are supported. Block verification runs on place-order POST, before payment. Draft GET/PUT/PATCH requests do not spend verification credits. CF7, WordPress registration and selected Gravity Forms/WPForms/Ninja Forms use the same policy.

Verification: WordPress 7.1.3, WooCommerce 11.2.1, CF7 6.2.1 on the isolated Docker stand on pi. 73 PHP integration assertions; real Store API missing-mailbox rejection and accepted catch-all order; Chrome settings and form checks. Plugin Check reports no errors (optional debug logging, bundled translation loader and global-name warnings remain). PHP 7.4 syntax validation is separate from a complete legacy-version runtime matrix. Gravity Forms was checked through its callback, not a licensed browser installation.

Build: `python3 tools/build_translations.py`, then `python3 tools/package_plugin.py`. ZIP includes only the plugin runtime, translations and GPL license. Stand mock API, tests, WordPress core, unrelated plugins and developer files are excluded. The output archive and SHA-256 are in `releases/`. No WordPress.org submission is performed.

## Русский

Настройки → ZBounce Email Validator позволяют выбрать разрешённые результаты во всех интеграциях. По умолчанию разрешены valid, risky/accept-all, unknown и недоступность API. Остальные результаты отклоняют форму. Встроенная проверка формата адреса самой формой сохраняется. Неопределённый SMTP-результат отличается от недоступности API. Эти результаты не кэшируются. Правила применяются к кэшу сразу, без повторной платной проверки.

Поддерживаются обычный checkout WooCommerce и Checkout Block. Проверка блока выполняется при POST оформления заказа до оплаты; промежуточные GET/PUT/PATCH не расходуют кредиты. CF7, регистрация WordPress и выбранные формы Gravity Forms/WPForms/Ninja Forms используют ту же политику.

Стенд на pi работает отдельным Docker-проектом. Для демонстрации задан тестовый ключ: адреса с префиксами good, catchall, rejected, disposable, unknown, nomx и failure воспроизводят соответствующие результаты. Замена тестового ключа своим ключом включает настоящий ZBounce API и расходует реальные кредиты. Исходящая почта на стенде отключена. Стенд и его данные не являются продакшеном. Не устанавливайте тестовый MU-плагин на рабочий сайт.

## 1.2.1 settings update

All API usage (including balance queries and cached result access) requires explicit saved license acceptance. The administrator save records acceptance time and user ID; input cannot forge these fields. Older configurations do not acquire acceptance automatically. The service agreement link is https://zbounce.net/license.html; the plugin source remains GPLv2-or-later.

Settings now show remaining credits, cached for up to 60 seconds per key; a fresh verification invalidates the balance cache and updates the displayed balance. Errors show unavailable rather than zero. The settings tester identifies the result, policy acceptance and source; its default cache checkbox avoids repeated charges, while disabling it requests a fresh paid check. Both paths retain administrator capability and nonce checks.

Русский: перед запросами к API необходимо явно принять соглашение сервиса. Время и пользователь принятия фиксируются сервером. После обновления старые настройки не получают согласие автоматически. В настройках видны баланс и тестер; галочка кэша по умолчанию включена. Без кэша выполняется новая платная проверка. Ошибка баланса не отображается как ноль. Настройки стенда после тестов восстановлены; принять соглашение нужно самостоятельно.

1.2.2: balance and tester are always visible at the top of settings. Disconnected controls are disabled, with setup guidance and a link to the external-verification switch; the administrator’s saved enablement is preserved. Browser checks cover both disabled and connected states.

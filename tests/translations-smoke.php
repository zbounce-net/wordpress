<?php
// Run with wp eval-file on the isolated stand. No external API requests.
$expected = [
    'ru_RU' => 'Требуется доступ администратора.',
    'de_DE' => 'Administratorzugriff erforderlich.',
    'fr_FR' => 'Accès administrateur requis.',
    'uk_UA' => 'Потрібен доступ адміністратора.',
    'zh_CN' => '需要管理员权限。',
];
$translation_checks = 0;
foreach ( $expected as $locale => $message ) {
    $filter = static function () use ( $locale ) { return $locale; };
    add_filter( 'determine_locale', $filter );
    unload_textdomain( 'zbounce-email-validator', true );
    \ZbEmailValidator\Translations::register();
    $actual = __( 'Administrator access required.', 'zbounce-email-validator' );
    if ( $actual !== $message ) {
        throw new RuntimeException( "$locale: got $actual" );
    }
    ++$translation_checks;
    remove_filter( 'determine_locale', $filter );
}
unload_textdomain( 'zbounce-email-validator', true );
echo "$translation_checks bundled locale loading checks passed.\n";

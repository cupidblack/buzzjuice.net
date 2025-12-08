const mix = require('laravel-mix');
const wpPot = require('wp-pot');

mix.setPublicPath('assets')
    .setResourceRoot('../')
    .sourceMaps(false)

    .js('assets/js/give-fee-recovery-admin.js', 'js/give-fee-recovery-admin.min.js')
    .js('assets/js/give-fee-recovery-public.js', 'js/give-fee-recovery-public.min.js')

    .css('assets/css/give-fee-recovery-admin.css', 'css/give-fee-recovery-admin.min.css')
    .css('assets/css/give-fee-recovery-frontend.css', 'css/give-fee-recovery-frontend.min.css');

mix.options({
    terser: {
        extractComments: (astNode, comment) => false,
        terserOptions: {
            format: {
                comments: false,
            },
        },
    },
});

if (mix.inProduction()) {
    wpPot({
        src: ['assets/js/*.js', 'includes/*.php', 'includes/**/*.php', 'src/**/*.php'],
        package: 'Give_Fee_Recovery',
        domain: 'give-fee-recovery',
        destFile: 'languages/give-fee-recovery.pot',
        bugReport: 'https://github.com/impress-org/give-fee-recovery/issues/new',
        team: 'GiveWP <info@givewp.com>',
    });
}

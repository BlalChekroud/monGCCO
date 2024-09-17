const Encore = require('@symfony/webpack-encore');

Encore
    // Indique à Encore où il doit générer les fichiers compilés
    .setOutputPath('public/build/')
    // Chemin utilisé pour accéder aux fichiers compilés depuis le navigateur
    .setPublicPath('/build')

    // Ajouter les fichiers d'entrée pour l'application
    .addEntry('app', './assets/app.js')

    // Active le hachage des fichiers pour les versions de production (ex: app.[hash].js)
    .enableVersioning(Encore.isProduction())

    // Active Sass/SCSS si vous utilisez Sass/SCSS
    .enableSassLoader()

    // Permet d'utiliser PostCSS si vous l'utilisez
    .enablePostCssLoader()
;

module.exports = Encore.getWebpackConfig();

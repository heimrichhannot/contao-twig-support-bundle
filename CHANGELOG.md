# Changelog
All notable changes to this project will be documented in this file.

## [0.2.0-DEV] - 2020-09-18
- added configuration to enable contao template loading
- made TwigTemplateLocator public

## [0.1.4] - 2020-09-18
- replaced symfony serializer with custom object normalizer

## [0.1.3] - 2020-09-17
- switch to symfony PSR-6 cache due strange errors in contao 4.4 with PSR-16 filesystem cache
- added ContaoWidgetNormalizer
- made normalization contao 4.4 compatible
- twig templates now only added in backend to TemplateLoader
- fixed bundle order for template loading
- fixed options not correctly loaded from widget

## [0.1.2] - 2020-09-17
- fixed wrong method call lead to navigation templates not rendered

## [0.1.1] - 2020-09-17
- add template comments in dev mode

## [0.1.0] - 2020-09-16
Initial release
# Changelog
All notable changes to this project will be documented in this file.

## [0.2.6] - 2020-12-08
- added some unit tests
- fixed options parameter not evaluated in TwigTemplateLocator::getTemplatePath()
- fixed template loading order not respected when no theme folder set

## [0.2.5] - 2020-09-23
- only add templates to contao template loader if they not exist -> fix error with rsce

## [0.2.4] - 2020-09-22
- fixed performance issue in dev mode
- added stopwatch to TwigTemplateLocator for better debugging
- fixed php lang level errors

## [0.2.3] - 2020-09-22
- fixed issue with regex TwigTemplateLocator::getPrefixedFiles()

## [0.2.2] - 2020-09-22
- fixed TypeError in TwigTemplateLocator

## [0.2.1] - 2020-09-22
- fixed missing BeforeParseTwigTemplateEvent::setTemplateName()

## [0.2.0] - 2020-09-21
- added configuration to enable contao template loading
- added TwigTemplateLocator::getTemplateGroup()
- added TwigTemplateLocator::getPrefixedFiles()
- changed TwigTemplateLocator::getTemplatePath() now respects themes folder
- changed TwigTemplateLocator::getTemplatePath() use configuration array instead of specific options parameter
- changed data structure in TwigTemplateLocator
- renamed TwigTemplateLocator::getTwigTemplatePaths() to generateContaoTwigTemplatePaths()
- made TwigTemplateLocator public
- removed templates from event as they can be loaded from TwigTemplateLoader service

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
# Changelog

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.8.5] - 2024-12-04
- Fixed: do not initialize contao framework at cache warmup

## [1.8.4] - 2024-11-19
- Fixed: do not throw an exception in backend if templates only exists in theme folder

## [1.8.3] - 2024-10-01
- Fixed: do not process templates already based on twig

## [1.8.2] - 2024-10-01
- Fixed: issues with different bundle structures

## [1.8.1] - 2024-09-30
- Fixed: issues with templates in Resources folder

## [1.8.0] - 2024-09-26
- Changed: better support for contao twig engine
- Changed: require contao 4.13
- Deprecated: TwigTemplateLoader::getTemplatesInPath()

## [1.7.0] - 2024-03-21
- Changed: modernized bundle structure

## [1.6.3] - 2023-03-21
- Fixed: compatibility with contao 4.13

## [1.6.2] - 2023-12-04
- Fixed: possible exception on cache warmup

## [1.6.1] - 20223-11-23
- Fixed: templates in project /contao/templates folder not found

## [1.6.0] - 2022-06-15
- Added: support for callables in contao templates ([#10])
- Changed: enhance error messages when rendering templates ([#10])
- Changed: raised minimum supported php version to 7.4 ([#10])

## [1.5.9] - 2022-06-02
- Fixed: warning with php 8 ([#9])

## [1.5.8] - 2022-03-25
- Fixed: exception if the templates folder does not exist ([#8], [@qzminski])

## [1.5.7] - 2022-02-10

- Fixed: typo in event dispatcher calls

## [1.5.6] - 2022-02-08

- Fixed: event dispatcher calls

## [1.5.5] - 2022-02-07

- Fixed: symfony deps

## [1.5.4] - 2022-02-07

- Fixed: symfony event dispatcher contracts dep

## [1.5.3] - 2022-02-07

- Fixed: symfony event dispatcher contracts dep

## [1.5.2] - 2022-02-07

- Fixed: symfony event dispatches to symfony 4+-compliant

## [1.5.1] - 2022-02-07

- Fixed: increased contao minimum version to 4.9

## [1.5.0] - 2022-02-07

- Fixed: dependencies for contao 4.13

## [1.4.2] - 2021-12-07

- Fixed: template list empty if initializeSystem hook not executed in RenderListener

## [1.4.1] - 2021-11-11

- Fixed: issue concerning twig template file extensions

## [1.4.0] - 2021-08-31

- Added: support for php 8

## [1.3.0] - 2021-08-27

- Added: support for new bundle structure convention (
  see [Symfony Best Practices](https://symfony.com/doc/4.4/bundles/best_practices.html#directory-structure))
- Fixed: wrong license file

## [1.2.1] - 2021-06-02

- fixed TwigTemplateLocator::getTemplatesInPath() method signature (changed $bundle type from Bundle to BundleInterface)
- fixed two warnings in TwigTemplateLocator

## [1.2.0] - 2021-06-01

- added TwigTemplateLocator::getTemplateContext()
- introduced TwigTemplateLocator::getTemplatesInPath() and deprecated TwigTemplateLocator::getTwigTemplatesInPath()
- fixed TwigTemplateLocator::getTemplateGroup() empty brackets in result label

## [1.1.1] - 2021-05-10

- fixed LoaderError when template folder symlinked to symlinked bundle

## [1.1.0] - 2021-04-16

- added option to skip templates

## [1.0.0] - 2021-04-14

- Add twig renderer class ([#6])

## [0.2.18] - 2021-05-10

Same as 1.1.1

## [0.2.17] - 2021-04-16

Same as 1.1.0

## [0.2.16] - 2021-04-14

Same as 1.0

## [0.2.15] - 2021-04-08

- fixed TwigFrontendTemplate not working when template loader is not activated
- fixed misleading exception when template not found in TwigFrontendTemplate

## [0.2.14] - 2021-04-06

- allow twig 3
- changed TwigFrontendTemplate to use RenderListener::render() method

## [0.2.13] - 2021-03-19

- fixed warning due unused binds

## [0.2.12] - 2021-03-19

- add widget variable to widget templates (#4)

## [0.2.11] - 2021-03-04

- added TwigFrontendTemplate class

## [0.2.10] - 2021-01-26

- fixed TwigTemplateLocator::getTemplates() not respecting extension flag (#3)

## [0.2.9] - 2021-01-11

- template cache refactored as service
- template cache has now a configurable lifetime
- template cache now saved project cache folder
- added github actions test workflow setup

## [0.2.8] - 2020-12-17

- clean invalid cache entries in TwigTemplateLocator

## [0.2.7] - 2020-12-15

- added missing return types on two methods in TwigTemplateLocator

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

[#10]: https://github.com/heimrichhannot/contao-twig-support-bundle/pull/10
[#9]: https://github.com/heimrichhannot/contao-twig-support-bundle/pull/9
[#8]: https://github.com/heimrichhannot/contao-twig-support-bundle/pull/8
[#6]: https://github.com/heimrichhannot/contao-twig-support-bundle/pull/6
[@qzminski]: https://github.com/qzminski
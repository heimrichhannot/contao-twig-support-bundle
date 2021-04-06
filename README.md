# Contao Twig Support Bundle
[![Latest Stable Version](https://img.shields.io/packagist/v/heimrichhannot/contao-twig-support-bundle.svg)](https://packagist.org/packages/heimrichhannot/contao-twig-support-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/heimrichhannot/contao-twig-support-bundle.svg)](https://packagist.org/packages/heimrichhannot/contao-twig-support-bundle)
[![CI](https://github.com/heimrichhannot/contao-twig-support-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/heimrichhannot/contao-twig-support-bundle/actions/workflows/ci.yml)
[![Coverage Status](https://coveralls.io/repos/github/heimrichhannot/contao-twig-support-bundle/badge.svg)](https://coveralls.io/github/heimrichhannot/contao-twig-support-bundle)

A bundle to enable [twig](https://twig.symfony.com/) templates support in contao. Templates are handled like .html5 templates in contao, e.g. you can select them in your custom templates select in the backend and they will be outputted in the frontend. 

## Features
* enable twig support in contao
    * use twig to write contao templates
    * select twig templates in your module/element custom template selection or override default templates in twig
    * support for all types of templates including widgets
* use this bundle as library to work with twig templates in your own bundles with extensive template loader
* uses caching for fast performance (is disabled in dev mode)
* minimal dependencies ;)

## Usage

### Setup

Install via contao manager or composer:

    composer require heimrichhannot/contao-twig-support-bundle
    
    
### Your first template

1. If you want to use this bundle to enable contao to support twig templates, you need to enable the template loader. You just need to add following configuration entries to your config.yml: 

    ```yaml
    # config/config.yml (Contao 4.9) or app/Resources/config/config.yml (Contao 4.4)
    huh_twig_support:
        enable_template_loader: true
    ```

1. Now you can just create a twig template like `ce_text_custom.html.twig` and add it to your projekt `template` folder (in contao 4.4: `app/Resources/views`) and you can select the template as custom Template in the text content element. You can override every core template that is parsed by `parseTemplate` and `parseWidget` hook.  You can also add templates from bundles.

### Widgets

As already written, this bundle also allows to override widget templates. Since twig templates are not rendered in the default widget/template scope as contao templates, you can't use `$this` to get variables. Instead twig support bundles passes the widget instance so you can use the widget object to get the variable content instand of this, for examle `{{ widget.name }}` or `{{ widget.class }}`. 

### Twig and contao cavets

User input is encoded by contao, so you need to add the raw filter to variables if you need to output html.

### Use project templates folder for twig templates in contao 4.4

If you want to use the project templates folder also in contao 4.4, just add following lines to your config.yml:

```yaml
# app/Ressouces/config/config.yml
twig:
  paths:
    '%kernel.project_dir%/templates': ~
```

## Developers

### Events

Event | Description
----- | -----------
BeforeParseTwigTemplateEvent | Dispatched after twig templates was found.
BeforeRenderTwigTemplate | Dispatched before twig templates is rendered.

### Use twig in your bundle

This bundle is a great base if you want to use twig in your own bundles. Use the `HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator` service to load your templates, where you need them while keeping the contao template hierarchy (you can override a bundle template in your project template folder or in another bundle which is loaded after the bundle).

```php
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use Twig\Environment;

function showTemplateLocatorUsage(TwigTemplateLocator $templateLocator, Environment $twig) {
    $twigTemplatePath = $templateLocator->getTemplatePath('my_custom_template');
    $buffer = $twig->render($twigTemplatePath, ['foo' => 'bar']);
}
```

There is also a `TwigFrontendTemplate` class which inherits from the contao FrontendTemplate class and can be used to render twig template in contao context and use all hooks and template class functions.

```php
use HeimrichHannot\TwigSupportBundle\Template\TwigFrontendTemplate;

$template = new TwigFrontendTemplate('my_custom_template');
$template->setData(['foo' => 'bar']);
return $template->getResponse();
```

## Configuration reference

```yaml
huh_twig_support:

  # Enable twig templates to be loaded by contao (enabled overriding core templates and select twig templates in the contao backend).
  enable_template_loader: false

  # Template cache lifetime in seconds with a value 0 causing cache to be stored indefinitely (i.e. until the files are deleted).
  template_cache_lifetime: 0
```

## Credits
* thanks to [m-vo](https://github.com/m-vo) and his [Twig Bundle](https://github.com/m-vo/contao-twig) which implementation was in inspiration and inital basis for this bundle.
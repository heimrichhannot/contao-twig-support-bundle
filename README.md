# Contao Twig Support Bundle
[![Latest Stable Version](https://img.shields.io/packagist/v/heimrichhannot/contao-twig-support-bundle.svg)](https://packagist.org/packages/heimrichhannot/contao-twig-support-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/heimrichhannot/contao-twig-support-bundle.svg)](https://packagist.org/packages/heimrichhannot/contao-twig-support-bundle)
[![CI](https://github.com/heimrichhannot/contao-twig-support-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/heimrichhannot/contao-twig-support-bundle/actions/workflows/ci.yml)
[![Coverage Status](https://coveralls.io/repos/github/heimrichhannot/contao-twig-support-bundle/badge.svg)](https://coveralls.io/github/heimrichhannot/contao-twig-support-bundle)

A bundle to enable extensive [twig](https://twig.symfony.com/) templates support in contao. Just activate the template loader and you can work with twig templates as they were .html5 templates. This means, you can select twig templates in the custom template select in the contao backend and you can override any template in your project template folder or in a bundle.
As a developer, you can use this bundle to work with twig templates like with contao templates, e.g. use simple template names, template groups and rely on the contao template hierarchy. 

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

This bundle is a great base if you want to use twig in your own bundles.

#### Template locator

Use the `TwigTemplateLocator` service to load your templates, where you need them while keeping the contao template hierarchy (you can override a bundle template in your project template folder or in another bundle which is loaded after the bundle).

Get all Templates with prefix (like Controller::getTemplateGroup): `TwigTemplateLocator::getTemplateGroup()`

```php
class CustomContainer
{
    /**
     * @var HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator
     */
    protected $templateLocator;

    public function onTemplateOptionsCallback()
    {
        return $this->templateLocator->getTemplateGroup('subscribe_button_');
    }
}
```

Get the twig path from an template name:

```php
use HeimrichHannot\TwigSupportBundle\Filesystem\TwigTemplateLocator;
use Twig\Environment;

function showTemplateLocatorUsage(TwigTemplateLocator $templateLocator, Environment $twig) {
    $twigTemplatePath = $templateLocator->getTemplatePath('my_custom_template');
    $buffer = $twig->render($twigTemplatePath, ['foo' => 'bar']);
}
```

#### Template renderer

Use the `TwigTemplateRenderer` service to render a template by template name. The renderer adds template comments in dev mode as contao does for html5 templates.  There are additional config options to customize the renderer or render a specific twig template instead of using the template locator to get the correct path to an template name. If you need to add specific logic applied before or after rendering, we recommend to [decorate the service](https://symfony.com/doc/current/service_container/service_decoration.html).

```php
use HeimrichHannot\TwigSupportBundle\Renderer\TwigTemplateRenderer;
use HeimrichHannot\TwigSupportBundle\Renderer\TwigTemplateRendererConfiguration;

class MyCustomController {

    /** @var TwigTemplateRenderer */
    protected $twigTemplateRenderer;

    public function renderAction(string $templateName = 'mod_default', array $templateData = []): string
    {
        $buffer = $this->twigTemplateRenderer->render($templateName, $templateData);
        
        // Or pass some configuration:
        
        $configuration = (new TwigTemplateRendererConfiguration())
                                ->setShowTemplateComments(false)
                                ->setTemplatePath('@AcmeBundle/module/mod_custom.html.twig')
                                ->setThrowExceptionOnError(false);
                                
        return $this->twigTemplateRenderer->render($templateName, $templateData, $configuration);
    }
}
```

#### TwigFrontendTemplate

You can use the `TwigFrontendTemplate` class to work with a twig template as it's a normal contao frontend template object. It inherits from the contao FrontendTemplate class and can be used to render twig template in contao context and use all hooks and template class functions.

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
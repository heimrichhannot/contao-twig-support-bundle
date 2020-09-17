# Contao Twig Support Bundle

A bundle to enable [twig](https://twig.symfony.com/) templates support in contao. Templates are handled like .html5 templates in contao, e.g. you can select them in your custom templates select in the backend and they will be outputted in the frontend.

## Features
* use twig to write contao templates
* select twig templates in your module/element custom template selection or override default templates in twig.

## Usage

### Setup

Install via contao manager or composer:

    composer require heimrichhannot/contao-twig-support-bundle
    
### Your first template

Just create a twig template like `ce_text_custom.html.twig` and add it to your projekt `template` folder (in contao 4.4: `app/Resources/views`) and you can select the template as custom Template in the text content element. You can override every core template that is parsed by `parseTemplate` and `parseWidget` hook. 

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

## Credits
* thanks to [m-vo](https://github.com/m-vo) and his [Twig Bundle](https://github.com/m-vo/contao-twig) which set the base for this bundle
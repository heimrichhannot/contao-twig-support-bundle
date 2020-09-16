# Contao Twig Support Bundle

A bundle to enable [twig](https://twig.symfony.com/) templates support in contao. Templates are handled like .html5 templates in contao, e.g. you can select them in your custom templates select in the backend and they will be outputted in the frontend.

## Features
* use twig to write contao templates
* select twig templates in your module/element custom template selection or override default templates in twig.

## Usages

### Twig and contao cavets

User input is encoded by contao, so you need to add the raw filter to variables if you need to output html.

## Developers

### Events

Event | Description
----- | -----------
BeforeParseTwigTemplateEvent | Dispatched after twig templates was found.
BeforeRenderTwigTemplate | Dispatched before twig templates is rendered.

## Credits
* thanks to [m-vo](https://github.com/m-vo) and his [Twig Bundle](https://github.com/m-vo/contao-twig) which set the base for this bundle
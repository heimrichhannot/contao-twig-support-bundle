<?php

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['initializeSystem']['huh_twig_support'] = [
    \HeimrichHannot\TwigSupportBundle\EventListener\RenderListener::class,
    'onInitializeSystem'
];
$GLOBALS['TL_HOOKS']['parseTemplate']['huh_twig_support']    = [
    \HeimrichHannot\TwigSupportBundle\EventListener\RenderListener::class,
    'onParseTemplate'
];
$GLOBALS['TL_HOOKS']['parseWidget']['huh_twig_support']      = [
    \HeimrichHannot\TwigSupportBundle\EventListener\RenderListener::class,
    'onParseWidget'
];
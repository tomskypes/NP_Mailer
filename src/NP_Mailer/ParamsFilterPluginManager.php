<?php
/**
 * This file is part of the NP_Mailer package.
 * 
 * Copyright (c) Nikola Posa <posa.nikola@gmail.com>
 * 
 * For full copyright and license information, please refer to the LICENSE file, 
 * located at the package root folder.
 */

namespace NP_Mailer;

use Zend\ServiceManager\AbstractPluginManager;

/**
 * Plugin manager implementation for mailer params filters.
 *
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class ParamsFilterPluginManager extends AbstractPluginManager
{
    /**
     * Default set of adapters
     *
     * @var array
     */
    protected $factories = array(
        'translator' => 'NP_Mailer\ParamsFilter\Translator::factory',
        'htmlbodybuilder' => 'NP_Mailer\ParamsFilter\HtmlBodyBuilder::factory',
    );

    /**
     * @param  mixed $plugin
     * @return void
     * @throws RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof ParamsFilter\FilterInterface) {
            // we're okay
            return;
        }

        throw new \RuntimeException(sprintf(
            'Plugin of type %s is invalid; must implement %s\ParamsFilter\FilterInterface',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
            __NAMESPACE__
        ));
    }
}

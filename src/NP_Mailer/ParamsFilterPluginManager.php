<?php
/*
 * NP_Mailer
 * Copyright (C) 2008-2013  Nikola Posa
 * 
 * This program is free software: you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License as published by 
 * the Free Software Foundation, either version 3 of the License, or 
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License 
 * along with this program.If not, see <http://www.gnu.org/licenses/>.
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

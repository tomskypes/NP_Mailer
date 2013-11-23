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

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Mail\Transport\Sendmail as SendmailTransport;

/**
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class MailerFactory implements FactoryInterface
{
    /**
     * Plugin manager for loading params filters
     *
     * @var null|ParamsFilterPluginManager
     */
    protected static $paramsFilters = null;
    
    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \NP_Mailer\Mailer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $mailerConfig = $serviceLocator->get('Config');
        $mailerConfig = isset($mailerConfig['mailer']) ? $mailerConfig['mailer'] : array();
        
        $transport = (isset($mailerConfig['transport'])) 
                ? $serviceLocator->get($mailerConfig['transport']) 
                //Use Sendmail by default
                : new SendmailTransport();
        $mailConfigurations = (isset($mailerConfig['configs'])) ? $mailerConfig['configs'] : array();
        
        $mailer = new Mailer($transport, $mailConfigurations);
        
        if (isset($mailerConfig['defaults'])) {
            $mailer->setDefaults($mailerConfig['defaults']);
        }
        
        if (isset($mailerConfig['params_filters'])) {
            $this->injectParamsFilters($mailer, $mailerConfig['params_filters']);
        }
        
        return $mailer;
    }
    
    protected function injectParamsFilters(Mailer $mailer, array $paramsFilters)
    {
        foreach ($paramsFilters as $info) {
            $filter = self::paramsFilterFactory($info['name'], isset($info['options']) ? $info['options'] : null);
            $mailer->addParamsFilter($filter);
        }
    }
    
    public static function getParamsFiltersPluginManager()
    {
        if (self::$paramsFilters === null) {
            self::$paramsFilters = new ParamsFilterPluginManager();
        }
        
        return self::$paramsFilters;
    }
    
    public static function setParamsFiltersPluginManager(ParamsFilterPluginManager $paramsFilters)
    {
        self::$paramsFilters = $paramsFilters;
    }
    
    public static function paramsFilterFactory($filterName, $options = array())
    {
        if ($filterName instanceof ParamsFilter\FilterInterface) {
            //Already object
            $filter = $filterName;
        } else {
            $filter = self::getParamsFiltersPluginManager()->get($filterName);
        }

        if ($options) {
            $filter->setOptions($options);
        }

        return $filter;
    }
}

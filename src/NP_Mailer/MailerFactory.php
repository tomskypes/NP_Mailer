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
     * @var ServiceLocatorInterface 
     */
    protected $services;

    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \NP_Mailer\Mailer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
        
        $mailerConfig = $serviceLocator->get('Config');
        $mailerConfig = isset($mailerConfig['np_mailer']) ? $mailerConfig['np_mailer'] : array();
        
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
            $filter = $this->paramsFilterFactory($info['name'], isset($info['options']) ? $info['options'] : null);
            $mailer->addParamsFilter($filter);
        }
    }
    
    protected function getParamsFiltersPluginManager()
    {
        if (self::$paramsFilters === null) {
            self::$paramsFilters = $this->services->get('NP_MailerParamsFiltersManager');
        }
        
        return self::$paramsFilters;
    }
    
    public function setParamsFiltersPluginManager(ParamsFilterPluginManager $paramsFilters)
    {
        self::$paramsFilters = $paramsFilters;
    }
    
    public function paramsFilterFactory($filterName, $options = array())
    {
        if ($filterName instanceof ParamsFilter\FilterInterface) {
            //Already object
            $filter = $filterName;
        } else {
            $filter = $this->getParamsFiltersPluginManager()->get($filterName);
        }

        if ($options) {
            $filter->setOptions($options);
        }

        return $filter;
    }
}

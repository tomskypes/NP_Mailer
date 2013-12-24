<?php
/**
 * This file is part of the NP_Mailer package.
 * 
 * Copyright (c) Nikola Posa <posa.nikola@gmail.com>
 * 
 * For full copyright and license information, please refer to the LICENSE file, 
 * located at the package root folder.
 */

namespace NP_MailerTest;

use PHPUnit_Framework_TestCase;
use NP_Mailer\MailerFactory;

/**
 * @group mailer
 * 
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class MailerFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MailerFactory 
     */
    protected $factory;
    
    protected $serviceLocator;
    
    protected $config = array();

    protected function setUp()
    {
        $this->factory = new MailerFactory();
        $this->serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
    }
    
    protected function setConfig(array $config)
    {
        $this->serviceLocator->expects($this->once())
            ->method('get')
            ->with('Config')
            ->will($this->returnValue($config));
    }

    public function testMailerCreationWithNoConfiguration()
    {
        $this->setConfig(array());
        
        $mailer = $this->factory->createService($this->serviceLocator);
        
        $this->assertEmpty($mailer->getConfigs());
        $this->assertEmpty($mailer->getDefaults());
    }
    
    public function testDefaultTransportIsCreatedIfNotSupplied()
    {
        $this->setConfig(array());
        
        $mailer = $this->factory->createService($this->serviceLocator);
        
        $this->assertInstanceOf('Zend\Mail\Transport\TransportInterface', $mailer->getTransport());
    }
    
    public function testAddingDefaults()
    {
        $defaults = array(
            'subject' => 'Foobar',
            'from' => 'foo@bar.com',
        );
        $this->setConfig(array(
            'np_mailer' => array(
                'defaults' => $defaults
            )
        ));
        
        $mailer = $this->factory->createService($this->serviceLocator);
        
        $this->assertEquals($defaults, $mailer->getDefaults());
    }
    
    public function testAddingMailerConfigs()
    {
        $configs = array(
            'foo' => array(
                'subject' => 'Foobar',
                'from' => 'foo@bar.com',
            ),
            'test' => array(
                'to' => 'test123@example.com',
                'from' => 'test@example.com',
            )
        );
        $this->setConfig(array(
            'np_mailer' => array(
                'configs' => $configs
            )
        ));
        
        $mailer = $this->factory->createService($this->serviceLocator);
        
        $this->assertCount(count($configs), $mailer->getConfigs());
        $this->assertEquals($configs, $mailer->getConfigs());
    }
    
    public function testInjectingParamsFiltersThroughPluginManager()
    {
        $paramsFiltersConf = array(
            array(
                'name' => 'translator',
                'options' => array(),
            ),
            array(
                'name' => 'htmlbodybuilder',
                'options' => array(),
            ),
        );
        $this->setConfig(array(
            'np_mailer' => array(
                'params_filters' => $paramsFiltersConf
            )
        ));
        
        $paramsFilters = $this->getMock('NP_Mailer\ParamsFilterPluginManager', array('get'), array(), '', false);
        foreach ($paramsFiltersConf as $i => $conf) {
            $paramsFilters->expects($this->at($i))
            ->method('get')
            ->with($conf['name'])
            ->will($this->returnValue($this->getMock('NP_Mailer\ParamsFilter\FilterInterface')));
        }
        
        $this->factory->setParamsFiltersPluginManager($paramsFilters);
        
        $mailer = $this->factory->createService($this->serviceLocator);
        
        $this->assertCount(count($paramsFiltersConf), $mailer->getParamsFilters());
    }
    
    public function testInjectingAlreadyInstantiatedParamsFilters()
    {
        $paramsFiltersConf = array(
            array(
                'name' => $this->getMock('NP_Mailer\ParamsFilter\FilterInterface'),
            ),
        );
        $this->setConfig(array(
            'np_mailer' => array(
                'params_filters' => $paramsFiltersConf
            )
        ));
        
        $paramsFilters = $this->getMock('NP_Mailer\ParamsFilterPluginManager', array('get'), array(), '', false);
        $paramsFilters->expects($this->never())->method('get');
        $this->factory->setParamsFiltersPluginManager($paramsFilters);
        
        $mailer = $this->factory->createService($this->serviceLocator);
        
        $this->assertCount(count($paramsFiltersConf), $mailer->getParamsFilters());
    }
    
    public function testInjectingParamsFiltersAndSettingItsOptions()
    {
        $paramsFiltersName = '';
        $paramsFilterOpts = array(
            'layout' => 'test',
        );
        $this->setConfig(array(
            'np_mailer' => array(
                'params_filters' => array(
                    array(
                        'name' => $paramsFiltersName,
                        'options' => $paramsFilterOpts,
                    ),
                )
            )
        ));
        
        $paramsFilter = $this->getMock('NP_Mailer\ParamsFilter\FilterInterface');
        $paramsFilter->expects($this->once())
            ->method('setOptions')
            ->with($paramsFilterOpts);
        
        $paramsFilters = $this->getMock('NP_Mailer\ParamsFilterPluginManager', array('get'), array(), '', false);
        $paramsFilters->expects($this->once())
            ->method('get')
            ->with($paramsFiltersName)
            ->will($this->returnValue($paramsFilter));
        
        $this->factory->setParamsFiltersPluginManager($paramsFilters);
        
        $mailer = $this->factory->createService($this->serviceLocator);
        
        $this->assertNotEmpty($mailer->getParamsFilters());
    }
}

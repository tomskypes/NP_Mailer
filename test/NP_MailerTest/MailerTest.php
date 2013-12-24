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
use NP_Mailer\Mailer;

/**
 * @group mailer
 * 
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class MailerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var NP_Mailer\Mailer 
     */
    protected $mailer;
    
    protected $transport;
    
    protected $filters;

    protected function setUp()
    {
        $this->transport = $this->getMock('Zend\Mail\Transport\TransportInterface');
        $this->mailer = new Mailer($this->transport);
    }
    
    protected function mockFilters($params, $filteredParams = null)
    {
        $this->filters = $this->getMockBuilder('Zend\Filter\FilterChain')
            ->disableOriginalConstructor()
            ->getMock();
        
        if (!$filteredParams) {
            $filteredParams = $params;
        }
        $this->filters->expects($this->once())
            ->method('filter')
            ->with($params)
            ->will($this->returnValue($filteredParams));
        
        $this->mailer->setParamsFilters($this->filters);
    }

    public function testFiltersShouldBeChainable()
    {
        $this->assertInstanceOf('Zend\Filter\FilterChain', $this->mailer->getParamsFilters());
    }
    
    public function testFiltersInvokedWhenBuildingMessage()
    {
        $params = array(
            'subject' => 'Test',
            'to' => 'test@test.com'
        );
        
        $this->mockFilters($params);
        
        $this->mailer->buildMessage($params);
    }
    
    public function testMessageAssembledFromValidParameters()
    {
        $params = array(
            'subject' => 'Test',
            'to' => 'test@test.com',
            'bogus' => 'foobar',
        );
        
        $message = $this->mailer->buildMessage($params);
        
        $this->assertEquals($params['subject'], $message->getSubject());
        $this->assertTrue($message->getTo()->has($params['to']));
    }
    
    public function testDefaultsMergedWithSuppliedParams()
    {
        $defaults = array(
            'from' => 'admin@example.com',
        );
        $params = array(
            'subject' => 'Test',
            'to' => 'test@test.com',
            'bogus' => 'foobar',
        );
        
        $this->mailer->setDefaults($defaults);
        $message = $this->mailer->buildMessage($params);
        
        $this->assertEquals($params['subject'], $message->getSubject());
        $this->assertTrue($message->getTo()->has($params['to']));
        $this->assertTrue($message->getFrom()->has($defaults['from']));
    }
    
    public function testSendingMail()
    {
        $params = array(
            'subject' => 'Test',
            'to' => 'test@test.com',
            'bogus' => 'foobar',
        );
        
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf('Zend\Mail\Message'));
        
        $this->mailer->send($params);
    }
    
    public function testAddingConfigs()
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
        
        $this->mailer->addConfigs($configs);
        
        $this->assertEquals($configs, $this->mailer->getConfigs());
    }
    
    public function testSendingMailFromConfig()
    {
        $configName = 'test';
        $configParams = array(
            'subject' => 'Test',
            'to' => 'test123@example.com',
            'from' => 'test@example.com',
        );
        $this->mailer->addConfig($configName, $configParams);
        
        $message = $this->mailer->send(array(
            'body' => 'test test test',
        ), $configName);
        
        $this->assertEquals($configParams['subject'], $message->getSubject());
        $this->assertTrue($message->getTo()->has($configParams['to']));
        $this->assertTrue($message->getFrom()->has($configParams['from']));
    }
}

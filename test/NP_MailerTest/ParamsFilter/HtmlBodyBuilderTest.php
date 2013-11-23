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

namespace NP_MailerTest\ParamsFilter;

use PHPUnit_Framework_TestCase;
use NP_Mailer\ParamsFilter\HtmlBodyBuilder;
use Zend\View\Model\ViewModel;

/**
 * @group mailer
 * @group mailer_params_filter
 * 
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class HtmlBodyBuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var HtmlBodyBuilder 
     */
    protected $builder;
    
    protected $viewRenderer;

    protected function setUp()
    {
        $this->builder = new HtmlBodyBuilder();
    }
    
    protected function mockViewRenderer()
    {
        $this->viewRenderer = $this->getMock('Zend\View\Renderer\RendererInterface');
        $this->builder->setViewRenderer($this->viewRenderer);
    }
    
    public function testSettingLayoutTemplateNameThroughOptions()
    {
        $this->builder->setOptions(array(
            'layout_template' => 'test',
        ));
        $this->assertEquals('test', $this->builder->getLayoutTemplate());
    }

    public function testCreatingBodyFromHtmlString()
    {
        $html = '<p>test <strong>test</strong></p>';
        
        $params = array(
            'subject' => 'Test',
            'bodyHtml' => $html,
        );
        
        $filtered = $this->builder->filter($params);
        
        $this->assertArrayHasKey('body', $filtered);
        $this->assertArrayNotHasKey('bodyHtml', $filtered); //initial html should be removed
        
        $body = $filtered['body'];
        $this->assertInstanceOf('Zend\Mime\Message', $body);
        
        $parts = $body->getParts();
        $this->assertCount(1, $parts);
        
        $part = current($parts);
        $this->assertInstanceOf('Zend\Mime\Part', $part);
        $this->assertRegExp('/html/', $part->type);
        $this->assertEquals($html, $part->getContent());
    }
    
    public function testCreatingMultipartBodyFromTextAndHtml()
    {
        $text = 'test test';
        $html = '<p>test <strong>test</strong></p>';
        
        $params = array(
            'subject' => 'Test',
            'bodyText' => $text,
            'bodyHtml' => $html,
        );
        
        $filtered = $this->builder->filter($params);
        
        $this->assertArrayNotHasKey('bodyText', $filtered);
        
        $body = $filtered['body'];
        
        $this->assertTrue($body->isMultiPart());
        
        $parts = $body->getParts();
        $this->assertCount(2, $parts);
        
        $textPart = $parts[0];
        $this->assertInstanceOf('Zend\Mime\Part', $textPart);
        $this->assertRegExp('/plain/', $textPart->type);
        $this->assertEquals($text, $textPart->getContent());
    }
    
    /**
     * @expectedException DomainException
     * @expectedExceptionMessage renderer
     */
    public function testRenderViewModelExceptionIsRaisedWhenRendererNotSet()
    {
        $params = array(
            'subject' => 'Test',
            'viewModel' => new ViewModel(),
        );
        
        $this->builder->filter($params);
    }
    
    public function testHtmlBodyFromRenderedViewModel()
    {
        $model = new ViewModel(array('foo' => 'bar'));
        $html = '<p>test <strong>test</strong></p>';
        $params = array(
            'viewModel' => $model,
        );
        
        $this->mockViewRenderer();
        $this->viewRenderer->expects($this->once())
            ->method('render')
            ->with($model)
            ->will($this->returnValue($html));
        
        $filtered = $this->builder->filter($params);
        
        $this->assertArrayNotHasKey('viewModel', $filtered);
        
        $htmlPart = $filtered['body']->getParts()[0];
        $this->assertEquals($html, $htmlPart->getContent());
    }
    
    public function testHtmlBodyFromRenderedViewModelWithLayout()
    {
        $model = new ViewModel(array('foo' => 'bar'));
        $partialHtml = '<p>test <strong>test</strong></p>';
        $html = '<html><body>' . $partialHtml . '</body></html>';
        $layoutTemplate = 'layout/template';
        
        $params = array(
            'viewModel' => $model,
        );
        
        $this->mockViewRenderer();
        $this->viewRenderer->expects($this->at(0))
            ->method('render')
            ->with($model)
            ->will($this->returnValue($partialHtml));
        $this->viewRenderer->expects($this->at(1))
            ->method('render')
            ->with($this->isInstanceOf('Zend\View\Model\ViewModel'))
            ->will($this->returnValue($html));
        
        $this->builder->setLayoutTemplate($layoutTemplate);
        $filtered = $this->builder->filter($params);
        
        $htmlPart = $filtered['body']->getParts()[0];
        $this->assertEquals($html, $htmlPart->getContent());
    }
    
    public function testHtmlBodyFromRenderedViewTemplate()
    {
        $template = 'template/name';
        $html = '<html><body><p>test <strong>test</strong></p></body></html>';
        
        $params = array(
            'viewTemplate' => $template,
        );
        
        $this->mockViewRenderer();
        $this->viewRenderer->expects($this->once())
            ->method('render')
            ->with($this->isInstanceOf('Zend\View\Model\ViewModel'))
            ->will($this->returnValue($html));
        
        $filtered = $this->builder->filter($params);
        
        $this->assertArrayNotHasKey('viewTemplate', $filtered);
        
        $htmlPart = $filtered['body']->getParts()[0];
        $this->assertEquals($html, $htmlPart->getContent());
    }
    
    public function testViewParamsProcessingSetsTranslatorTextDomain()
    {
        $translatorTextDomain = 'test';
        $params = array(
            'translatorTextDomain' => $translatorTextDomain,
            'viewModel' => new ViewModel(),
        );
        
        $viewRenderer = $this->getMock('Zend\View\Renderer\PhpRenderer');
        $translateHelper = $this->getMockBuilder('Zend\I18n\View\Helper\Translate')
                    ->disableOriginalConstructor()
                    ->getMock();
        $translateHelper->expects($this->once())->method('setTranslatorTextDomain')->with($translatorTextDomain);
        $viewRenderer->expects($this->once())->method('plugin')->with('translate')->will($this->returnValue($translateHelper));
        $viewRenderer->expects($this->once())->method('render');
        
        $this->builder->setViewRenderer($viewRenderer);
        
        $this->builder->filter($params);
    }
}

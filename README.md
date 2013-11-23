# NP_Mailer

NP_Mailer is a ZF2 Module which facilitates and simplifies sending of email messages.

## Installation

You can install this module either by cloning this project into your `./vendor/` directory, 
or using composer, which is more recommended:

1. Add this project into your composer.json:

    ```json
    "require": {
        "nikolaposa/np_mailer": "dev-master"
    }
    ```

2. Tell composer to download NP_Mailer by running update command:

    ```bash
    $ php composer.phar update
    ```
    
For more information about composer itself, please refer to [getcomposer.org](http://getcomposer.org/).

### Enable the module in your `application.config.php`:

    <?php
    return array(
        'modules' => array(
            // ...
            'NP_Mailer',
        ),
        // ...
    );
    
## Usage

### Mailer service

`NP_Mailer\Mailer` service is the central point of this module and the one that the consumer should address 
when sending some email message. It is available under the `Mailer` service name.

### Configuration

Mailer service can be configured in order to tune its functionality. `NP_Mailer\MailerFactory`, which is a 
default factory responsible for creating Mailer instance, is aware of the mailer configuration key, through 
which all the Mailer options can be supplied. Those are:

* `transport` - `Zend\Mail\Transport\TransportInterface` instance; `Sendmail` transport is used by default.
* `defaults` - default mail message parameters (i.e. subject, to, from and similar)
* `params_filters` - list of parameters filters that should be used. `HtmlBodyBuilder` and `Translator` are used by default.
* `configs` - mailing configurations

### Parameters filters

Mailer service has a sub-component - parameters filter implementations, which make powerful mechanism for 
preprocessing and inflecting certain mail parameters.

#### Default filters

Out of the box NP_Mailer provides filters for some common use cases. 

##### HtmlBodyBuilder (`NP_Mailer\ParamsFilter\HtmlBodyBuilder`)

This filter makes sure that HTML body is assembled the right way. It is also capable of rendering HTML body if 
it is supplied in form of a ViewModel.

HtmlBodyBuilder is aware of some custom mail parameters, which are specific only for this filter. Those are:
* `bodyHtml` - if supplied, it will be used as a HTML body of a mail message
* `bodyText` - if supplied along with the `bodyHtml`, those two will constitute multi-part message body
* `viewModel` - when supplied, message HTML body will be formed by rendering this view model
* `viewTemplate` - similar to the `viewModel` param, except that it should be name of a template, whose rendered content should be used as a message body

In relation with view-specific parameters (`viewModel` and `viewTemplate`), this filter provides ability to have some 
layout template rendered along with the actual `viewModel` and `viewTemplate` output respectively. That can be achieved 
by setting HtmlBodyBuilder's `layoutTemplate` option, which should ben name of a template that should be used as a mail layout.

##### Translator (`NP_Mailer\ParamsFilter\Translator`)

Filter capable of translating certain email message fields. Translatable fields are configured through its 
`$translatableParams` property and appropriate setter method.

### Mailing configurations

Mailer service provide ability to have "ready-to-send" mail configurations, with pre-configured mail message parameters 
(i.e. subject, to, from, etc.), so that sending of a mail can be triggered only by supplying name of certain configuration.

Those can be supplied directly through the Mailer's API (`addConfig(s)` method) or through configuration - `configs` option. 
Examples of such configuration might look like:

    <?php
    return array(
        // ...
        'mailer' => array(
            // ...
            'configs' => array(
                'foo' => array(
                    'subject' => 'Foobar',
                    'from' => 'foo@bar.com',
                ),
                'test' => array(
                    'to' => 'test123@example.com',
                    'from' => 'test@example.com',
                )
            )
            // ...
        ),
        // ...
    );
    
In this example, two mail configurations are specified, one named `foo` and the other named `test`.

### Usage

#### Sending basic mail

    ```php
    $mailer->send(array(
       'to' => 'test@example.com',
       'subject' => 'Test',
       'body' => 'Hello world!',
    ));
    ```
    
#### Sending HTML email

    ```php
    $mailer->send(array(
       'to' => 'test@example.com',
       'subject' => 'Test',
       'bodyHtml' => '<html><body><p>Hello world!</p></body></html>',
    ));
    ```
    
#### HTML mail from ViewModel

    ```php
    $viewModel = new \Zend\View\Model\ViewModel(array(
        'foo' => 'bar',
        'baz' => 'bat',
    ));
    $viewModel->setTemplate('template/name');
     
    $mailer->send(array(
       'to' => 'test@example.com',
       'subject' => 'Test',
       'viewModel' => $viewModel,
    ));
    ```
    
#### Sending configured mail

    ```php
        $mailer->send(array(
       'bodyHtml' => '<html><body><p>Hello world!</p></body></html>',
    ), 'someMailiingConfigurationName');
    ```
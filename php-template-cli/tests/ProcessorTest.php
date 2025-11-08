<?php

use PHPUnit\Framework\TestCase;
use YourNamespace\Template\Processor;

class ProcessorTest extends TestCase
{
    protected $processor;

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }

    public function testProcessTemplateWithVariables()
    {
        $template = 'Hello, {{name}}!';
        $variables = ['name' => 'John'];
        $expectedOutput = 'Hello, John!';

        $result = $this->processor->process($template, $variables);

        $this->assertEquals($expectedOutput, $result);
    }

    public function testProcessTemplateWithMissingVariables()
    {
        $template = 'Hello, {{name}}!';
        $variables = [];
        $expectedOutput = 'Hello, !';

        $result = $this->processor->process($template, $variables);

        $this->assertEquals($expectedOutput, $result);
    }

    public function testProcessTemplateWithMultipleVariables()
    {
        $template = 'Hello, {{firstName}} {{lastName}}!';
        $variables = ['firstName' => 'John', 'lastName' => 'Doe'];
        $expectedOutput = 'Hello, John Doe!';

        $result = $this->processor->process($template, $variables);

        $this->assertEquals($expectedOutput, $result);
    }

    public function testProcessTemplateWithNoVariables()
    {
        $template = 'Hello, World!';
        $variables = [];
        $expectedOutput = 'Hello, World!';

        $result = $this->processor->process($template, $variables);

        $this->assertEquals($expectedOutput, $result);
    }
}
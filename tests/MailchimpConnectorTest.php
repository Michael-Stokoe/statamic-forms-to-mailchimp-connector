<?php

declare(strict_types=1);

namespace Stokoe\FormsToMailchimpConnector\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stokoe\FormsToMailchimpConnector\MailchimpConnector;
use Statamic\Forms\Form;
use Statamic\Forms\Submission;

class MailchimpConnectorTest extends TestCase
{
    public function test_it_has_correct_handle_and_name(): void
    {
        $connector = new MailchimpConnector;
        
        $this->assertEquals('mailchimp', $connector->handle());
        $this->assertEquals('Mailchimp', $connector->name());
    }

    public function test_it_returns_fieldset(): void
    {
        $connector = new MailchimpConnector;
        $fieldset = $connector->fieldset();
        
        $this->assertIsArray($fieldset);
        $this->assertNotEmpty($fieldset);
        
        $handles = array_column($fieldset, 'handle');
        $this->assertContains('api_key', $handles);
        $this->assertContains('list_id', $handles);
        $this->assertContains('email_field', $handles);
    }

    public function test_it_handles_missing_config_gracefully(): void
    {
        Log::shouldReceive('warning')->once();
        
        $connector = new MailchimpConnector;
        $submission = $this->createMockSubmission();
        
        // Should not throw exception with missing config
        $connector->process($submission, []);
        
        $this->assertTrue(true);
    }

    public function test_it_handles_invalid_email_gracefully(): void
    {
        Log::shouldReceive('warning')->once();
        
        $connector = new MailchimpConnector;
        $submission = $this->createMockSubmission(['email' => 'invalid-email']);
        
        $config = [
            'api_key' => 'test-key-us1',
            'list_id' => 'test123',
            'email_field' => 'email',
        ];
        
        $connector->process($submission, $config);
        
        $this->assertTrue(true);
    }

    public function test_it_makes_successful_api_call(): void
    {
        Log::shouldReceive('info')->once();
        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('withHeaders')->andReturnSelf();
        Http::shouldReceive('post')->andReturn(
            \Mockery::mock()->shouldReceive('successful')->andReturn(true)->getMock()
        );
        
        $connector = new MailchimpConnector;
        $submission = $this->createMockSubmission(['email' => 'test@example.com']);
        
        $config = [
            'api_key' => 'test-key-us1',
            'list_id' => 'test123',
            'email_field' => 'email',
        ];
        
        $connector->process($submission, $config);
        
        $this->assertTrue(true);
    }

    private function createMockSubmission(array $data = []): Submission
    {
        $submission = \Mockery::mock(Submission::class);
        $form = \Mockery::mock(Form::class);
        
        $form->shouldReceive('handle')->andReturn('test_form');
        $submission->shouldReceive('form')->andReturn($form);
        $submission->shouldReceive('id')->andReturn('test_id');
        $submission->shouldReceive('data')->andReturn($data);
        
        return $submission;
    }
}

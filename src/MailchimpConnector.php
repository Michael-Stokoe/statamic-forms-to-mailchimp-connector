<?php

declare(strict_types=1);

namespace Stokoe\FormsToMailchimpConnector;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stokoe\FormsToWherever\Contracts\ConnectorInterface;
use Statamic\Forms\Submission;

class MailchimpConnector implements ConnectorInterface
{
    public function handle(): string
    {
        return 'mailchimp';
    }

    public function name(): string
    {
        return 'Mailchimp';
    }

    public function fieldset(): array
    {
        return [
            [
                'handle' => 'api_key',
                'field' => [
                    'type' => 'text',
                    'display' => 'API Key',
                    'instructions' => 'Your Mailchimp API key',
                    'validate' => 'required',
                ],
            ],
            [
                'handle' => 'list_id',
                'field' => [
                    'type' => 'text',
                    'display' => 'List ID',
                    'instructions' => 'The Mailchimp list ID to add subscribers to',
                    'validate' => 'required',
                ],
            ],
            [
                'handle' => 'email_field',
                'field' => [
                    'type' => 'text',
                    'display' => 'Email Field',
                    'instructions' => 'Form field containing the email address',
                    'default' => 'email',
                ],
            ],
            [
                'handle' => 'double_optin',
                'field' => [
                    'type' => 'toggle',
                    'display' => 'Double Opt-in',
                    'instructions' => 'Require email confirmation',
                    'default' => false,
                ],
            ],
            [
                'handle' => 'field_mapping',
                'field' => [
                    'type' => 'grid',
                    'display' => 'Field Mapping',
                    'instructions' => 'Map form fields to Mailchimp merge fields',
                    'fields' => [
                        [
                            'handle' => 'form_field',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Form Field',
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'mailchimp_field',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Mailchimp Merge Tag',
                                'instructions' => 'e.g. FNAME, LNAME, PHONE',
                                'width' => 50,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function process(Submission $submission, array $config): void
    {
        $apiKey = $config['api_key'] ?? null;
        $listId = $config['list_id'] ?? null;
        $emailField = $config['email_field'] ?? 'email';
        $doubleOptin = $config['double_optin'] ?? false;
        $fieldMapping = $config['field_mapping'] ?? [];

        if (!$apiKey || !$listId) {
            Log::warning('Mailchimp connector: Missing API key or list ID', [
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
            ]);
            return;
        }

        $formData = $submission->data()->toArray();
        $email = $formData[$emailField] ?? null;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Mailchimp connector: Invalid or missing email', [
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
                'email_field' => $emailField,
                'email' => $email,
            ]);
            return;
        }

        // Extract datacenter from API key
        $datacenter = substr($apiKey, strpos($apiKey, '-') + 1);
        if (!$datacenter) {
            Log::error('Mailchimp connector: Invalid API key format', [
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
            ]);
            return;
        }

        $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$listId}/members";

        // Build merge fields from mapping
        $mergeFields = new \stdClass();
        foreach ($fieldMapping as $mapping) {
            $formField = $mapping['form_field'] ?? '';
            $mailchimpField = $mapping['mailchimp_field'] ?? '';

            if ($formField && $mailchimpField && isset($formData[$formField])) {
                $mergeFields->{$mailchimpField} = $formData[$formField];
            }
        }

        $payload = [
            'email_address' => $email,
            'status' => $doubleOptin ? 'pending' : 'subscribed',
            'merge_fields' => $mergeFields,
        ];

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('Mailchimp subscriber added successfully', [
                    'email' => $email,
                    'list_id' => $listId,
                    'status' => $payload['status'],
                    'form' => $submission->form()->handle(),
                    'submission_id' => $submission->id(),
                ]);
            } else {
                $error = $response->json();
                Log::error('Mailchimp API error', [
                    'status' => $response->status(),
                    'error' => $error['detail'] ?? 'Unknown error',
                    'errors' => $error['errors'] ?? [],
                    'full_response' => $error,
                    'email' => $email,
                    'form' => $submission->form()->handle(),
                    'submission_id' => $submission->id(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Mailchimp connector exception', [
                'error' => $e->getMessage(),
                'email' => $email,
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
            ]);
        }
    }
}

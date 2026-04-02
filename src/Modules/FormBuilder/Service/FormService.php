<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\FormBuilder\Service;

use NextPressBuilder\Core\Repository\FormRepository;
use NextPressBuilder\Core\Repository\SubmissionRepository;

/**
 * Business logic for forms: CRUD, submission handling, validation.
 */
class FormService
{
    public function __construct(
        private readonly FormRepository $formRepo,
        private readonly SubmissionRepository $submissionRepo,
        private readonly SpamProtectionService $spamService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Process a form submission from the frontend.
     *
     * @param string               $formSlug
     * @param array<string, mixed> $data
     * @param string               $ip
     * @param string               $userAgent
     * @param string               $referrer
     * @return array{success: bool, message: string, errors?: array}
     */
    public function handleSubmission(string $formSlug, array $data, string $ip, string $userAgent, string $referrer): array
    {
        // 1. Find form.
        $form = $this->formRepo->findBySlug($formSlug);
        if (!$form) {
            return ['success' => false, 'message' => 'Form not found.'];
        }

        $settings = json_decode(wp_json_encode($form->settings ?? []), true) ?? [];

        // 2. Spam check.
        $spamSettings = $settings['spam_protection'] ?? [];
        $spamSettings['form_slug'] = $formSlug;
        $spamResult = $this->spamService->check($data, $spamSettings, $ip);
        if ($spamResult !== null) {
            return ['success' => false, 'message' => $spamResult];
        }

        // 3. Validate fields.
        $fields = json_decode(wp_json_encode($form->fields ?? []), true) ?? [];
        $errors = $this->validateSubmission($data, $fields);
        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        // 4. Clean data (remove internal fields).
        $cleanData = $this->cleanSubmissionData($data);

        // 5. Store submission.
        $submissionId = $this->submissionRepo->create([
            'form_id'    => (int) $form->id,
            'data'       => $cleanData,
            'ip_address' => $ip,
            'user_agent' => substr($userAgent, 0, 500),
            'referrer'   => substr($referrer, 0, 500),
            'status'     => 'unread',
        ]);

        // 6. Send notifications.
        $this->notificationService->send($form, $cleanData, $submissionId);

        $successMsg = $settings['success_message'] ?? 'Thank you! Your submission has been received.';

        return ['success' => true, 'message' => $successMsg];
    }

    /**
     * Validate submitted data against form field definitions.
     *
     * @param array<string, mixed> $data
     * @param array<int, mixed>    $fields
     * @return array<string, string> Field errors.
     */
    private function validateSubmission(array $data, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            $field = (object) $field;
            $key = $field->key ?? $field->name ?? '';
            if (!$key) continue;

            $value = $data[$key] ?? null;
            $type = $field->type ?? 'text';

            // Skip display-only fields.
            if (in_array($type, ['html', 'divider', 'heading'], true)) continue;

            // Required check.
            $required = !empty($field->required);
            if ($required && ($value === null || $value === '' || $value === [])) {
                $label = $field->label ?? $key;
                $errors[$key] = "{$label} is required.";
                continue;
            }

            if ($value === null || $value === '') continue;

            // Type-specific validation.
            $validation = isset($field->validation) ? (array) $field->validation : [];

            if ($type === 'email' && !is_email((string) $value)) {
                $errors[$key] = 'Please enter a valid email address.';
            }

            if (isset($validation['minLength']) && strlen((string) $value) < (int) $validation['minLength']) {
                $errors[$key] = $validation['customMessage'] ?? "Must be at least {$validation['minLength']} characters.";
            }

            if (isset($validation['maxLength']) && strlen((string) $value) > (int) $validation['maxLength']) {
                $errors[$key] = "Must not exceed {$validation['maxLength']} characters.";
            }

            if (isset($validation['pattern']) && !preg_match($validation['pattern'], (string) $value)) {
                $errors[$key] = $validation['customMessage'] ?? 'Invalid format.';
            }

            if ($type === 'number') {
                if (!is_numeric($value)) {
                    $errors[$key] = 'Must be a number.';
                } else {
                    if (isset($validation['min']) && (float) $value < (float) $validation['min']) {
                        $errors[$key] = "Must be at least {$validation['min']}.";
                    }
                    if (isset($validation['max']) && (float) $value > (float) $validation['max']) {
                        $errors[$key] = "Must not exceed {$validation['max']}.";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Remove internal fields from submission data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function cleanSubmissionData(array $data): array
    {
        $internal = ['npb_hp_field', 'npb_form_timestamp', 'g-recaptcha-response', 'cf-turnstile-response'];
        foreach ($internal as $key) {
            unset($data[$key]);
        }
        // Sanitize all string values.
        return array_map(fn($v) => is_string($v) ? sanitize_text_field($v) : $v, $data);
    }

    /**
     * Seed a default contact form if none exist.
     */
    public function seedDefaults(): void
    {
        if ($this->formRepo->count() > 0) {
            return;
        }

        $this->formRepo->create([
            'slug'     => 'contact',
            'name'     => 'Contact Form',
            'fields'   => [
                ['key'=>'full_name','type'=>'text','label'=>'Full Name','required'=>true,'width'=>'50%','validation'=>['minLength'=>2,'maxLength'=>100]],
                ['key'=>'email','type'=>'email','label'=>'Email Address','required'=>true,'width'=>'50%'],
                ['key'=>'phone','type'=>'phone','label'=>'Phone Number','required'=>false,'width'=>'50%'],
                ['key'=>'service','type'=>'select','label'=>'Service Needed','required'=>false,'width'=>'50%','options'=>['General Inquiry','Get a Quote','Schedule Service','Other']],
                ['key'=>'message','type'=>'textarea','label'=>'Message','required'=>true,'validation'=>['minLength'=>10,'maxLength'=>2000]],
            ],
            'settings' => [
                'success_message'  => 'Thank you! We will get back to you within 24 hours.',
                'submit_button'    => 'Send Message',
                'notifications'    => [
                    'admin' => ['enabled'=>true,'to'=>[get_option('admin_email')],'subject'=>'New contact from {field:full_name}','reply_to'=>'{field:email}'],
                    'user_confirmation' => ['enabled'=>true,'to'=>'{field:email}','subject'=>'Thank you for contacting {site_name}'],
                ],
                'spam_protection'  => ['min_submit_time'=>3,'max_submissions_per_hour'=>5],
                'redirect_url'     => '',
            ],
        ]);

        $this->formRepo->create([
            'slug'     => 'quick-quote',
            'name'     => 'Quick Quote',
            'fields'   => [
                ['key'=>'name','type'=>'text','label'=>'Your Name','required'=>true,'width'=>'50%'],
                ['key'=>'phone','type'=>'phone','label'=>'Phone','required'=>true,'width'=>'50%'],
                ['key'=>'email','type'=>'email','label'=>'Email','required'=>true,'width'=>'100%'],
                ['key'=>'service','type'=>'select','label'=>'Service','required'=>true,'options'=>['Plumbing','Electrical','HVAC','Roofing','Other']],
                ['key'=>'details','type'=>'textarea','label'=>'Brief Description','required'=>false,'validation'=>['maxLength'=>500]],
            ],
            'settings' => [
                'success_message'  => 'Quote request received! We\'ll call you shortly.',
                'submit_button'    => 'Request Quote',
                'notifications'    => [
                    'admin' => ['enabled'=>true,'to'=>[get_option('admin_email')],'subject'=>'New quote request from {field:name}','reply_to'=>'{field:email}'],
                    'user_confirmation' => ['enabled'=>false],
                ],
                'spam_protection'  => ['min_submit_time'=>2,'max_submissions_per_hour'=>10],
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\FormBuilder\Service;

/**
 * Sends email notifications on form submissions.
 *
 * Supports admin notification and user confirmation emails.
 * Template variables: {field:name}, {form_name}, {site_name}, {submission_date}.
 */
class NotificationService
{
    /**
     * Send notifications for a form submission.
     *
     * @param object               $form       Form record with settings.
     * @param array<string, mixed> $data       Submitted field values.
     * @param int                  $submissionId
     */
    public function send(object $form, array $data, int $submissionId): void
    {
        $settings = json_decode(wp_json_encode($form->settings ?? []), true) ?? [];
        $notifications = $settings['notifications'] ?? [];

        // Admin notification.
        $admin = $notifications['admin'] ?? null;
        if ($admin && !empty($admin['enabled'])) {
            $this->sendAdminNotification($admin, $form, $data);
        }

        // User confirmation.
        $confirm = $notifications['user_confirmation'] ?? null;
        if ($confirm && !empty($confirm['enabled'])) {
            $this->sendUserConfirmation($confirm, $form, $data);
        }

        // Fire hook for custom integrations.
        do_action('npb_form_submitted', (int) $form->id, $data, $submissionId);
    }

    /**
     * Send admin notification email.
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $data
     */
    private function sendAdminNotification(array $config, object $form, array $data): void
    {
        $to = $config['to'] ?? [get_option('admin_email')];
        if (is_string($to)) $to = [$to];

        $subject = $this->resolveVariables(
            $config['subject'] ?? 'New {form_name} submission',
            $form,
            $data
        );

        $body = $this->buildAdminEmailBody($form, $data);

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // Reply-To from submitted email field.
        $replyTo = $config['reply_to'] ?? '';
        if ($replyTo) {
            $resolvedReply = $this->resolveVariables($replyTo, $form, $data);
            if (is_email($resolvedReply)) {
                $headers[] = "Reply-To: {$resolvedReply}";
            }
        }

        // CC.
        $cc = $config['cc'] ?? [];
        if (is_array($cc)) {
            foreach ($cc as $ccEmail) {
                if (is_email($ccEmail)) {
                    $headers[] = "Cc: {$ccEmail}";
                }
            }
        }

        wp_mail(implode(',', $to), $subject, $body, $headers);

        do_action('npb_form_notification_sent', (int) $form->id, 'admin', $to);
    }

    /**
     * Send user confirmation email.
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $data
     */
    private function sendUserConfirmation(array $config, object $form, array $data): void
    {
        $toTemplate = $config['to'] ?? '{field:email}';
        $to = $this->resolveVariables($toTemplate, $form, $data);

        if (!is_email($to)) {
            return; // Can't send without valid email.
        }

        $subject = $this->resolveVariables(
            $config['subject'] ?? 'Thank you for contacting {site_name}',
            $form,
            $data
        );

        $body = $this->buildConfirmationEmailBody($form, $data);

        wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);

        do_action('npb_form_notification_sent', (int) $form->id, 'confirmation', [$to]);
    }

    /**
     * Build HTML email body for admin notification.
     *
     * @param array<string, mixed> $data
     */
    private function buildAdminEmailBody(object $form, array $data): string
    {
        $siteName = get_bloginfo('name');
        $formName = $form->name ?? 'Form';
        $date = wp_date('F j, Y \a\t g:i A');

        $html = "<div style='font-family:-apple-system,sans-serif;max-width:600px;margin:0 auto'>";
        $html .= "<div style='background:#0f172a;color:#fff;padding:24px;border-radius:8px 8px 0 0'>";
        $html .= "<h2 style='margin:0;font-size:18px'>New {$formName} Submission</h2>";
        $html .= "<p style='margin:4px 0 0;color:#94a3b8;font-size:13px'>{$siteName} &middot; {$date}</p></div>";
        $html .= "<div style='background:#fff;border:1px solid #e5e7eb;border-top:none;padding:24px;border-radius:0 0 8px 8px'>";

        // Remove internal fields.
        $skipFields = ['npb_hp_field', 'npb_form_timestamp', 'g-recaptcha-response', 'cf-turnstile-response'];

        foreach ($data as $key => $value) {
            if (in_array($key, $skipFields, true)) continue;
            $label = ucwords(str_replace(['_', '-'], ' ', $key));
            $displayValue = is_array($value) ? implode(', ', $value) : esc_html((string) $value);
            $html .= "<div style='padding:10px 0;border-bottom:1px solid #f3f4f6'>";
            $html .= "<div style='font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.03em'>{$label}</div>";
            $html .= "<div style='font-size:14px;color:#09090b;margin-top:4px'>{$displayValue}</div></div>";
        }

        $html .= "</div></div>";
        return $html;
    }

    /**
     * Build HTML email body for user confirmation.
     *
     * @param array<string, mixed> $data
     */
    private function buildConfirmationEmailBody(object $form, array $data): string
    {
        $siteName = get_bloginfo('name');
        $name = $data['full_name'] ?? $data['name'] ?? $data['first_name'] ?? 'there';

        $html = "<div style='font-family:-apple-system,sans-serif;max-width:600px;margin:0 auto'>";
        $html .= "<div style='background:#0f172a;color:#fff;padding:24px;border-radius:8px 8px 0 0'>";
        $html .= "<h2 style='margin:0;font-size:18px'>{$siteName}</h2></div>";
        $html .= "<div style='background:#fff;border:1px solid #e5e7eb;border-top:none;padding:32px 24px;border-radius:0 0 8px 8px'>";
        $html .= "<p style='font-size:15px;color:#09090b'>Hi {$name},</p>";
        $html .= "<p style='font-size:14px;color:#52525b;line-height:1.6'>Thank you for reaching out to us. We have received your message and will get back to you as soon as possible.</p>";
        $html .= "<p style='font-size:13px;color:#a1a1aa;margin-top:24px'>Best regards,<br><strong>{$siteName}</strong></p>";
        $html .= "</div></div>";
        return $html;
    }

    /**
     * Resolve template variables in a string.
     *
     * @param array<string, mixed> $data
     */
    private function resolveVariables(string $template, object $form, array $data): string
    {
        $template = str_replace('{form_name}', $form->name ?? 'Form', $template);
        $template = str_replace('{site_name}', get_bloginfo('name'), $template);
        $template = str_replace('{submission_date}', wp_date('Y-m-d H:i:s'), $template);

        // Resolve {field:xxx} placeholders.
        $template = preg_replace_callback('/\{field:(\w+)\}/', function ($matches) use ($data) {
            return (string) ($data[$matches[1]] ?? '');
        }, $template);

        return $template;
    }
}

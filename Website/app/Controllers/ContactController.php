<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Lead;
use App\Services\AuditService;

final class ContactController extends Controller
{
    public function contact(array $params = []): void
    {
        $data = [
            'name' => trim((string) $this->request->input('name', '')),
            'email' => trim((string) $this->request->input('email', '')),
            'phone' => trim((string) $this->request->input('phone', '')),
            'subject' => trim((string) $this->request->input('subject', '')),
            'message' => trim((string) $this->request->input('message', '')),
        ];

        $errors = Validator::required($data, ['name', 'email', 'message']);
        if (!Validator::email($data['email'])) {
            $errors['email'] = lang_text('Định dạng email không hợp lệ.', 'Invalid email format.');
        }

        if ($errors) {
            with_old($data);
            flash('error', lang_text('Vui lòng kiểm tra các trường mẫu liên hệ.', 'Please check contact form fields.'));
            $this->redirect('/#contact');
        }

        Lead::createContact($data);
        AuditService::log('create', 'contact_messages', null, ['email' => $data['email']]);

        flash('success', lang_text('Cảm ơn. ', 'Thank you. We will contact you soon.'));
        $this->redirect('/#contact');
    }

    public function demoRequest(array $params = []): void
    {
        $data = [
            'name' => trim((string) $this->request->input('name', '')),
            'organization' => trim((string) $this->request->input('organization', '')),
            'phone' => trim((string) $this->request->input('phone', '')),
            'email' => trim((string) $this->request->input('email', '')),
            'province' => trim((string) $this->request->input('province', '')),
            'message' => trim((string) $this->request->input('message', '')),
        ];

        $errors = Validator::required($data, ['name', 'organization', 'email']);
        if (!Validator::email($data['email'])) {
            $errors['email'] = lang_text('Định dạng email không hợp lệ.', 'Invalid email format.');
        }

        if ($errors) {
            with_old($data);
            flash('error', lang_text('Tải trọng yêu cầu tư vấn không hợp lệ.', 'Consultation request payload is invalid.'));
            $this->redirect('/#consult');
        }

        Lead::createDemo($data);
        AuditService::log('create', 'demo_requests', null, ['email' => $data['email']]);

        flash('success', lang_text('Yêu cầu tư vấn đã được gửi thành công.', 'Consultation request submitted successfully.'));
        $this->redirect('/#consult');
    }
}

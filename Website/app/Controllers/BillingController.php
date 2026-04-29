<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Lead;
use App\Models\PartnerProfile;
use App\Models\User;
use App\Services\AuditService;
use App\Services\MembershipService;

final class BillingController extends Controller
{
    public function index(array $params = []): void
    {
        $user = $this->currentUserRecord();
        $partnerProfile = null;
        if ($user !== null && in_array((string) ($user['role'] ?? ''), ['partner', 'farmer'], true)) {
            $partnerProfile = PartnerProfile::byUserId((int) $user['id']);
        }

        $plans = $this->plans();
        $selectedPlanCode = trim((string) old('plan_code', (string) $this->request->input('plan', 'pilot-0-6')));
        if (!isset($plans[$selectedPlanCode])) {
            $selectedPlanCode = 'pilot-0-6';
        }

        $prefill = [
            'plan_code' => $selectedPlanCode,
            'name' => trim((string) old('name', (string) ($user['full_name'] ?? ''))),
            'organization' => trim((string) old('organization', (string) ($partnerProfile['organization_name'] ?? ''))),
            'email' => trim((string) old('email', (string) ($user['email'] ?? ''))),
            'phone' => trim((string) old('phone', (string) ($partnerProfile['phone'] ?? ''))),
            'province' => trim((string) old('province', (string) ($partnerProfile['region'] ?? ''))),
            'message' => trim((string) old('message', '')),
        ];

        $this->view('public.billing', [
            'plans' => $plans,
            'selectedPlanCode' => $selectedPlanCode,
            'billingPrefill' => $prefill,
            'membershipStatus' => MembershipService::traceAccessSnapshot($user, $this->request->ip()),
            'userData' => $user,
        ]);
    }

    public function requestVip(array $params = []): void
    {
        $plans = $this->plans();
        $planCode = trim((string) $this->request->input('plan_code', ''));
        $data = [
            'name' => trim((string) $this->request->input('name', '')),
            'organization' => trim((string) $this->request->input('organization', '')),
            'email' => trim((string) $this->request->input('email', '')),
            'phone' => trim((string) $this->request->input('phone', '')),
            'province' => trim((string) $this->request->input('province', '')),
            'message' => trim((string) $this->request->input('message', '')),
        ];

        $errors = Validator::required($data, ['name', 'organization', 'email']);
        if (!Validator::email($data['email'])) {
            $errors['email'] = lang_text('Định dạng email không hợp lệ.', 'Invalid email format.');
        }
        if (!isset($plans[$planCode])) {
            $errors['plan_code'] = lang_text('Vui lòng chọn nhu cầu triển khai hợp lệ.', 'Please choose a valid deployment request type.');
        }

        if ($errors !== []) {
            with_old($data + ['plan_code' => $planCode]);
            flash('error', lang_text('Không thể gửi yêu cầu hỗ trợ triển khai. Vui lòng kiểm tra lại thông tin.', 'Cannot submit the deployment support request. Please review the form.'));
            $this->redirect('/billing#request');
        }

        $user = $this->currentUserRecord();
        $membershipStatus = MembershipService::traceAccessSnapshot($user, $this->request->ip());
        $plan = $plans[$planCode];

        $messageParts = [
            lang_text('Nhu cầu triển khai: ', 'Deployment request: ') . (string) $plan['title'],
            lang_text('Khung thời gian ưu tiên: ', 'Preferred timeline: ') . (string) $plan['duration_label'],
            lang_text('Phạm vi hỗ trợ mong muốn: ', 'Preferred support scope: ') . (string) $plan['focus_label'],
            lang_text('Đội ngũ AGRISORT-AI sẽ liên hệ để làm rõ bối cảnh vận hành, dữ liệu lô, QR và nhu cầu phối hợp.', 'The AGRISORT-AI team will follow up to clarify operational context, lot data, QR flow, and support needs.'),
        ];

        $currentPlanLabel = trim((string) ($membershipStatus['plan_label'] ?? ''));
        if ($currentPlanLabel !== '') {
            $messageParts[] = lang_text('Trạng thái truy cập hiện tại: ', 'Current access status: ') . $currentPlanLabel;
        }

        if ($data['message'] !== '') {
            $messageParts[] = '';
            $messageParts[] = $data['message'];
        }

        Lead::createDemo([
            'name' => $data['name'],
            'organization' => $data['organization'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'province' => $data['province'],
            'message' => implode(PHP_EOL, $messageParts),
        ]);

        clear_old();
        AuditService::log('create', 'deployment_support_requests', null, [
            'email' => $data['email'],
            'plan_code' => $planCode,
        ]);

        flash('success', lang_text('Yêu cầu hỗ trợ triển khai đã được gửi. Đội ngũ AGRISORT-AI sẽ liên hệ để trao đổi chi tiết.', 'Your deployment support request has been submitted. AGRISORT-AI will contact you to discuss the next steps.'));
        $this->redirect('/billing');
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function plans(): array
    {
        return [
            'pilot-0-6' => [
                'code' => 'pilot-0-6',
                'title' => lang_text('Pilot 0-6 tháng', '0-6 month pilot'),
                'badge' => lang_text('Khởi động', 'Kickoff'),
                'duration_label' => lang_text('0-6 tháng', '0-6 months'),
                'focus_label' => lang_text('HTX, trạm thu mua, cơ sở sơ chế', 'Cooperatives, buying stations, post-harvest facilities'),
                'summary' => lang_text('Phù hợp khi cần kiểm chứng máy phân loại, quy trình mã lô, QR truy xuất và cách vận hành tại điểm pilot đầu tiên.', 'Best when validating the sorting machine, lot-code flow, QR traceability, and on-site operation at the first pilot location.'),
                'features' => [
                    lang_text('Rà soát nhu cầu triển khai thực tế theo mùa vụ và loại nông sản.', 'Review real deployment needs by season and produce type.'),
                    lang_text('Làm rõ luồng Camera -> AI -> Phân loại -> QR theo mã lô.', 'Clarify the Camera -> AI -> Sorting -> lot-level QR workflow.'),
                    lang_text('Phù hợp cho giai đoạn thử nghiệm ban đầu tại một điểm vận hành cụ thể.', 'Fits the initial validation phase at one operating site.'),
                ],
                'recommended' => false,
            ],
            'rollout-6-24' => [
                'code' => 'rollout-6-24',
                'title' => lang_text('Mở rộng 6-24 tháng', '6-24 month rollout'),
                'badge' => lang_text('Khuyến nghị', 'Recommended'),
                'duration_label' => lang_text('6-24 tháng', '6-24 months'),
                'focus_label' => lang_text('Chuẩn hóa quy trình và mở rộng danh mục nông sản', 'Operational standardization and produce expansion'),
                'summary' => lang_text('Phù hợp khi đơn vị đã có dữ liệu pilot và muốn mở rộng sang nhiều mã lô, nhiều loại nông sản hoặc nhiều ca vận hành hơn.', 'Suitable when pilot data already exists and the team wants to scale across more lots, more produce categories, or more operating shifts.'),
                'features' => [
                    lang_text('Chuẩn hóa dữ liệu lô, ảnh, QR và biểu mẫu vận hành nội bộ.', 'Standardize lot data, imagery, QR outputs, and internal operating forms.'),
                    lang_text('Phù hợp cho đơn vị cần dùng đều trong suốt vụ hoặc nhiều đợt thu mua.', 'Fits teams operating continuously throughout a harvest season or repeated buying cycles.'),
                    lang_text('Tăng độ sẵn sàng cho phối hợp giữa web dashboard, app và máy phân loại.', 'Improves readiness across the web dashboard, app flows, and machine integration.'),
                ],
                'recommended' => true,
            ],
            'ecosystem-24-plus' => [
                'code' => 'ecosystem-24-plus',
                'title' => lang_text('Chuỗi liên kết 24+ tháng', '24+ month ecosystem support'),
                'badge' => lang_text('Dài hạn', 'Long-term'),
                'duration_label' => lang_text('24+ tháng', '24+ months'),
                'focus_label' => lang_text('Liên kết dữ liệu, mùa vụ và đối tác phân phối', 'Data, seasonal, and distribution alignment'),
                'summary' => lang_text('Dành cho đơn vị muốn tích hợp AGRISORT-AI vào vận hành dài hạn, theo dõi dữ liệu mùa vụ và xây dựng chuỗi minh bạch hơn.', 'Built for organizations planning longer-term AGRISORT-AI adoption with seasonal analytics and stronger downstream transparency.'),
                'features' => [
                    lang_text('Làm rõ nhu cầu lưu trữ dữ liệu 12-24 tháng và đồng bộ theo mã lô.', 'Clarify 12-24 month data retention and lot-level synchronization needs.'),
                    lang_text('Phù hợp khi cần chuẩn hóa chuỗi dữ liệu từ sơ chế đến phân phối.', 'Suitable when standardizing data flow from post-harvest handling to distribution.'),
                    lang_text('Tạo nền cho mở rộng IoT, phân tích chất lượng theo mùa vụ và hợp tác dài hạn.', 'Supports future IoT, seasonal quality analytics, and longer-term collaboration.'),
                ],
                'recommended' => false,
            ],
        ];
    }

    private function currentUserRecord(): ?array
    {
        $userId = (int) (current_user()['id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        return User::findById($userId);
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Validator;
use App\Models\ApiKey;
use App\Models\BlogPost;
use App\Models\CmsSection;
use App\Models\Lot;
use App\Models\LotPackage;
use App\Models\Setting;
use App\Models\TraceAssignment;
use App\Models\TraceEvent;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\SchemaSyncService;
use App\Services\TraceAssignmentService;
use App\Services\TranslateService;
use App\Services\UploadService;

final class AppAdminApiController extends AppApiBaseController
{
    public function dashboard(array $params = []): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $counts = array_merge(
            User::dashboardCounts(),
            User::supplyCounts(),
            Lot::counts(),
            LotPackage::counts(),
            ['published_blog_posts' => BlogPost::countPublished()]
        );

        $this->ok('Lấy dữ liệu dashboard thành công.', [
            'counts' => $counts,
            'pending_partners' => User::pendingPartners(),
            'pending_actors' => User::pendingActors(),
        ]);
    }

    public function users(array $params = []): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $hasVipColumn = SchemaSyncService::ensureUserVipColumn();

        $keyword = trim((string) $this->request->input('q', ''));
        $role = trim((string) $this->request->input('role', ''));
        $status = trim((string) $this->request->input('status', ''));
        $limit = (int) $this->request->input('limit', 300);
        if ($limit <= 0) {
            $limit = 300;
        }
        if ($limit > 1000) {
            $limit = 1000;
        }

        $where = [];
        $paramsSql = [];
        if ($role !== '') {
            $where[] = 'u.role = :role';
            $paramsSql['role'] = User::normalizeRole($role);
        }
        if ($status !== '') {
            $where[] = 'u.status = :status';
            $paramsSql['status'] = $status;
        }
        if ($keyword !== '') {
            $where[] = '(u.full_name LIKE :keyword OR u.email LIKE :keyword OR p.organization_name LIKE :keyword)';
            $paramsSql['keyword'] = '%' . $keyword . '%';
        }

        $vipSelectSql = $hasVipColumn ? 'u.vip_until,' : 'NULL AS vip_until,';

        $sql = 'SELECT
                    u.id, u.full_name, u.email, u.role, u.status, u.created_at, u.updated_at,
                    ' . $vipSelectSql . '
                    p.organization_name, p.representative_name, p.phone, p.address, p.region, p.tax_code
                FROM users u
                LEFT JOIN partner_profiles p ON p.user_id = u.id';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY u.created_at DESC LIMIT :limit';

        $stmt = Database::pdo()->prepare($sql);
        foreach ($paramsSql as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = array_map(static function (array $row): array {
            $row['is_vip'] = MembershipService::hasVip($row);
            $row['vip_until_label'] = MembershipService::formatVipUntil((string) ($row['vip_until'] ?? ''));

            return $row;
        }, $stmt->fetchAll());

        $this->ok('Lấy danh sách người dùng thành công.', $rows, [
            'total' => count($rows),
            'limit' => $limit,
        ]);
    }

    public function updateUserStatus(array $params): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        $payload = $this->payload();
        $status = (string) ($payload['status'] ?? 'active');
        if (!in_array($status, ['active', 'suspended', 'pending'], true)) {
            $this->fail('Trạng thái người dùng không hợp lệ.', 422);
            return;
        }
        if ($id <= 0 || User::findById($id) === null) {
            $this->fail('Không tìm thấy người dùng.', 404);
            return;
        }

        User::setStatus($id, $status);
        $this->ok('Cập nhật trạng thái người dùng thành công.', [
            'id' => $id,
            'status' => $status,
        ]);
    }

    public function updateUserVip(array $params): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        if (!SchemaSyncService::ensureUserVipColumn()) {
            $this->fail('Không thể kích hoạt cột mốc truy cập mở rộng trong cơ sở dữ liệu.', 500);
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        $user = $id > 0 ? User::findById($id) : null;
        if ($user === null) {
            $this->fail('Không tìm thấy người dùng.', 404);
            return;
        }

        $payload = $this->payload();
        $vipInput = trim((string) ($payload['vip_until'] ?? ''));
        if ($vipInput === '') {
            User::setVipUntil($id, null);

            $this->ok('Đã gỡ mốc truy cập mở rộng của người dùng.', [
                'id' => $id,
                'vip_until' => null,
                'vip_until_label' => null,
                'is_vip' => false,
            ]);
            return;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $vipInput . ' 23:59:59');
        if (!$date instanceof \DateTimeImmutable) {
            $this->fail('Ngày kích hoạt mở rộng không hợp lệ.', 422, [
                'vip_until' => 'Ngày kích hoạt mở rộng không hợp lệ.',
            ]);
            return;
        }

        $today = new \DateTimeImmutable('today');
        if ($date < $today) {
            $this->fail('Ngày kích hoạt mở rộng phải từ hôm nay trở đi.', 422, [
                'vip_until' => 'Ngày kích hoạt mở rộng phải từ hôm nay trở đi.',
            ]);
            return;
        }

        $vipUntil = $date->format('Y-m-d H:i:s');
        User::setVipUntil($id, $vipUntil);

        $freshUser = User::findById($id) ?? ['vip_until' => $vipUntil];
        $this->ok('Đã cập nhật mốc truy cập mở rộng.', [
            'id' => $id,
            'vip_until' => $vipUntil,
            'vip_until_label' => MembershipService::formatVipUntil((string) ($freshUser['vip_until'] ?? $vipUntil)),
            'is_vip' => MembershipService::hasVip($freshUser),
        ]);
    }

    public function approvePartner(array $params): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0 || User::findById($id) === null) {
            $this->fail('Không tìm thấy người dùng.', 404);
            return;
        }

        User::setStatus($id, 'active');
        $this->ok('Duyệt tài khoản đối tác thành công.', [
            'id' => $id,
            'status' => 'active',
        ]);
    }

    public function settings(array $params = []): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $this->ok('Lấy cấu hình hệ thống thành công.', Setting::all());
    }

    public function updateSettings(array $params = []): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $payload = $this->payload();
        $keys = [
            'auto_publish_lot',
            'smtp_host',
            'smtp_port',
            'smtp_user',
            'smtp_pass',
            'smtp_from',
            'qr_base_url',
            'google_translate_api_key',
        ];

        foreach ($keys as $key) {
            $value = (string) ($payload[$key] ?? '');
            if ($key === 'auto_publish_lot') {
                $value = $value === '1' ? '1' : '0';
            }
            Setting::set($key, $value);
        }

        $this->ok('Cập nhật cấu hình hệ thống thành công.', Setting::all());
    }

    public function showCms(array $params): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $section = trim((string) ($params['section_key'] ?? ''));
        if ($section === '') {
            $this->fail('Thiếu khóa phần CMS.', 422);
            return;
        }

        $this->ok('Lấy nội dung CMS thành công.', [
            'section_key' => $section,
            'vi' => CmsSection::getContent($section, 'vi'),
            'en' => CmsSection::getContent($section, 'en'),
        ]);
    }

    public function saveCms(array $params): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $section = trim((string) ($params['section_key'] ?? ''));
        if ($section === '') {
            $this->fail('Thiếu khóa phần CMS.', 422);
            return;
        }

        $payload = $this->payload();
        $existingVi = CmsSection::getContent($section, 'vi');
        $titleVi = trim((string) ($payload['title_vi'] ?? ''));
        $bodyVi = trim((string) ($payload['body_vi'] ?? ''));

        try {
            $imagePath = UploadService::image($this->request->file('image'));
        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage(), 422);
            return;
        }

        $vi = [
            'title' => $titleVi !== '' ? $titleVi : (string) ($existingVi['title'] ?? ''),
            'body' => $bodyVi !== '' ? $bodyVi : (string) ($existingVi['body'] ?? ''),
            'image' => $imagePath ?? ($existingVi['image'] ?? null),
        ];
        $en = TranslateService::structuredToEnglish($vi);

        CmsSection::save($section, $vi, $en);

        $this->ok('Cập nhật CMS thành công.', [
            'section_key' => $section,
            'vi' => CmsSection::getContent($section, 'vi'),
            'en' => CmsSection::getContent($section, 'en'),
        ]);
    }

    public function blogIndex(array $params = []): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $rows = BlogPost::all();
        $this->ok('Lấy danh sách bài viết quản trị thành công.', $rows, [
            'total' => count($rows),
        ]);
    }

    public function saveBlog(array $params = []): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $payload = $this->payload();
        $id = (int) ($payload['id'] ?? 0);
        $titleVi = trim((string) ($payload['title_vi'] ?? ''));
        $contentVi = trim((string) ($payload['content_vi'] ?? ''));

        $errors = Validator::required(
            ['title_vi' => $titleVi, 'content_vi' => $contentVi],
            ['title_vi', 'content_vi']
        );
        if ($errors !== []) {
            $this->fail('Dữ liệu bài viết chưa đầy đủ.', 422, $errors);
            return;
        }

        $existing = null;
        if ($id > 0) {
            $existing = BlogPost::find($id);
            if ($existing === null) {
                $this->fail('Không tìm thấy bài viết cần cập nhật.', 404);
                return;
            }
        }

        $titleEn = TranslateService::toEnglish($titleVi);
        $contentEn = TranslateService::toEnglish($contentVi);

        $status = (string) ($payload['status'] ?? ($existing['status'] ?? 'draft'));
        if (!in_array($status, ['draft', 'published'], true)) {
            $status = 'draft';
        }

        $excerptSourceVi = trim(strip_tags($contentVi)) !== '' ? trim(strip_tags($contentVi)) : $titleVi;
        $excerptVi = trim((string) ($payload['excerpt_vi'] ?? mb_substr($excerptSourceVi, 0, 160)));
        $seoTitle = trim((string) ($payload['seo_title'] ?? $titleVi));
        $seoDescription = trim((string) ($payload['seo_description'] ?? mb_substr($excerptSourceVi, 0, 200)));
        if (array_key_exists('excerpt_vi', $payload) && trim((string) $payload['excerpt_vi']) === '') {
            $excerptVi = mb_substr($excerptSourceVi, 0, 160);
        }
        $excerptEn = TranslateService::toEnglish($excerptVi);

        $slugInput = trim((string) ($payload['slug'] ?? ''));
        $slugSource = $slugInput !== '' ? $slugInput : (string) ($existing['slug'] ?? $titleVi);

        $data = [
            'slug' => normalize_slug($slugSource),
            'title_vi' => $titleVi,
            'title_en' => $titleEn,
            'content_vi' => $contentVi,
            'content_en' => $contentEn,
            'excerpt_vi' => $excerptVi,
            'excerpt_en' => $excerptEn,
            'status' => $status,
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
        ];

        try {
            $thumb = UploadService::image($this->request->file('thumbnail'));
            if ($thumb !== null) {
                $data['thumbnail'] = $thumb;
            }
        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage(), 422);
            return;
        }

        try {
            if ($id > 0) {
                BlogPost::update($id, $data);
                $row = BlogPost::find($id);
                $this->ok('Cập nhật bài viết thành công.', [
                    'post' => $row,
                ]);
                return;
            }

            $newId = BlogPost::create($data);
            $row = BlogPost::find($newId);
            $this->ok('Tạo bài viết thành công.', [
                'post' => $row,
            ], null, 201);
        } catch (\Throwable $e) {
            $this->fail('Có lỗi không xác định.', 409);
        }
    }

    public function apiKeysIndex(array $params = []): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $rows = ApiKey::listAll();
        $this->ok('Lấy danh sách API key thành công.', $rows, [
            'total' => count($rows),
        ]);
    }

    public function createApiKey(array $params = []): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $payload = $this->payload();
        $name = trim((string) ($payload['name'] ?? 'Mobile Integration'));
        if ($name === '') {
            $name = 'Mobile Integration';
        }

        $plainKey = 'agri_' . bin2hex(random_bytes(20));
        ApiKey::create($name, $plainKey);

        $this->ok('Tạo API key thành công.', [
            'name' => $name,
            'api_key' => $plainKey,
            'note' => 'Hãy lưu lại ngay, API key chỉ hiển thị một lần.',
        ], null, 201);
    }

    public function setLotPublish(array $params): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $lotId = (int) ($params['id'] ?? 0);
        if ($lotId <= 0 || Lot::findById($lotId) === null) {
            $this->fail('Không tìm thấy lô hàng.', 404);
            return;
        }

        $payload = $this->payload();
        $status = (string) ($payload['publish_status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published'], true)) {
            $this->fail('Trạng thái publish không hợp lệ.', 422);
            return;
        }

        Lot::setPublishStatus($lotId, $status);
        $this->ok('Cập nhật trạng thái công khai lô hàng thành công.', [
            'id' => $lotId,
            'publish_status' => $status,
        ]);
    }

    public function listTraceAssignments(array $params = []): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $rows = TraceAssignment::allDetailed();
        $this->ok('Lấy danh sách phân công truy xuất thành công.', $rows, [
            'total' => count($rows),
        ]);
    }

    public function saveTraceAssignment(array $params = []): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $adminId = (int) (($resolved['user']['id'] ?? 0));
        $payload = $this->payload();

        $entityType = TraceAssignmentService::normalizeEntityType((string) ($payload['entity_type'] ?? ''));
        $entityId = (int) ($payload['entity_id'] ?? 0);
        $stageCode = TraceAssignmentService::normalizeStageCode((string) ($payload['stage_code'] ?? ''));
        $actorUserId = (int) ($payload['actor_user_id'] ?? 0);

        if ($entityType === null || $entityId <= 0 || $actorUserId <= 0) {
            $this->fail('Dữ liệu phân công chưa hợp lệ.', 422);
            return;
        }
        if (!TraceAssignmentService::entityExists($entityType, $entityId)) {
            $this->fail('Đối tượng phân công không tồn tại.', 404);
            return;
        }
        if (User::findById($actorUserId) === null) {
            $this->fail('Người nhận phân công không tồn tại.', 404);
            return;
        }

        TraceAssignment::upsert($entityType, $entityId, $stageCode, $actorUserId, $adminId);
        $this->ok('Lưu phân công truy xuất thành công.', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'stage_code' => $stageCode,
            'actor_user_id' => $actorUserId,
        ]);
    }

    public function deleteTraceAssignment(array $params): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            $this->fail('ID phân công không hợp lệ.', 422);
            return;
        }

        TraceAssignment::deleteById($id);
        $this->ok('Xóa phân công truy xuất thành công.');
    }

    public function listTraceEvents(array $params = []): void
    {
        $resolved = $this->requireRole('admin');
        if ($resolved === null) {
            return;
        }

        $limit = (int) $this->request->input('limit', 200);
        if ($limit <= 0) {
            $limit = 200;
        }
        if ($limit > 1000) {
            $limit = 1000;
        }

        $rows = TraceEvent::listAllDetailed($limit);
        foreach ($rows as &$row) {
            $attachments = is_array($row['attachments'] ?? null) ? $row['attachments'] : [];
            foreach ($attachments as &$attachment) {
                if (!is_array($attachment)) {
                    continue;
                }
                $attachment['file_url'] = $this->fileUrl((string) ($attachment['file_path'] ?? ''));
            }
            unset($attachment);
            $row['attachments'] = $attachments;
        }
        unset($row);

        $this->ok('Lấy danh sách sự kiện truy xuất thành công.', $rows, [
            'total' => count($rows),
            'limit' => $limit,
        ]);
    }
}

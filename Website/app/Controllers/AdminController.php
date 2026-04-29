<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Controller;
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
use App\Services\AuditService;
use App\Services\SchemaSyncService;
use App\Services\TraceAssignmentService;
use App\Services\TraceBootstrapService;
use App\Services\TranslateService;
use App\Services\UploadService;

final class AdminController extends Controller
{
    public function dashboard(array $params = []): void
    {
        $counts = array_merge(User::dashboardCounts(), User::supplyCounts(), Lot::counts(), LotPackage::counts());
        $apiKeys = ApiKey::listAll();
        $blogPage = BlogPost::adminList(['page' => 1, 'per_page' => 5]);

        $counts['published_posts'] = BlogPost::countPublished();
        $counts['api_keys'] = count($apiKeys);

        $this->view('admin.dashboard', [
            'counts' => $counts,
            'partners' => array_slice(User::partnerList(), 0, 5),
            'lots' => array_slice(Lot::allWithPartner(), 0, 5),
            'apiKeys' => array_slice($apiKeys, 0, 5),
            'blogPosts' => $blogPage['rows'],
        ]);
    }

    private function preparedCmsSections(): array
    {
        $sections = [];

        foreach (CmsSection::all() as $row) {
            $key = (string) ($row['section_key'] ?? '');
            if ($key === '') {
                continue;
            }

            $vi = json_decode((string) ($row['content_json_vi'] ?? ''), true);
            $en = json_decode((string) ($row['content_json_en'] ?? ''), true);

            $sections[$key] = [
                'vi' => is_array($vi) ? $vi : [],
                'en' => is_array($en) ? $en : [],
            ];
        }

        return $sections;
    }

    /**
     * @return array<string,array{label:string,description:string}>
     */
    private function studioSectionCatalog(): array
    {
        return [
            'hero' => [
                'label' => lang_text('Hero trang chủ', 'Homepage hero'),
                'description' => lang_text('Khối đầu trang chủ: tiêu đề chính, mô tả và ảnh nổi bật.', 'Homepage opening block: main title, description, and hero image.'),
            ],
            'solution' => [
                'label' => lang_text('Giải pháp chuyên biệt', 'Specialized solution'),
                'description' => lang_text('Khối mô tả bài toán sau thu hoạch và cách AGRISORT-AI giải quyết cho nông sản Việt Nam.', 'Section describing the post-harvest problem and AGRISORT-AI solution fit.'),
            ],
            'technology' => [
                'label' => lang_text('Pipeline công nghệ', 'Technology pipeline'),
                'description' => lang_text('Khối mô tả camera, xử lý ảnh, AI và cơ cấu gạt thời gian thực.', 'Section covering cameras, image processing, AI, and real-time sorting control.'),
            ],
            'impact' => [
                'label' => lang_text('Hiệu quả kinh tế - xã hội', 'Economic and social impact'),
                'description' => lang_text('Khối số liệu vận hành, tiết kiệm lao động và minh bạch chuỗi cung ứng.', 'Metrics for operational performance, labor savings, and supply-chain transparency.'),
            ],
            'swot' => [
                'label' => lang_text('Hệ sinh thái và chuỗi giá trị', 'Ecosystem and value chain'),
                'description' => lang_text('Khối mô tả vai trò của nông dân, HTX, cơ sở sơ chế, doanh nghiệp và người mua cuối.', 'Section describing the farmer-to-buyer value chain around the system.'),
            ],
            'roadmap' => [
                'label' => lang_text('Lộ trình phát triển', 'Roadmap'),
                'description' => lang_text('Khối mô tả pilot, mở rộng sản phẩm và định hướng thị trường.', 'Section describing pilots, product expansion, and market direction.'),
            ],
            'team' => [
                'label' => lang_text('Đội ngũ thực hiện', 'Project team'),
                'description' => lang_text('Khối công khai tên và vai trò của thành viên, giảng viên hướng dẫn.', 'Section publishing team member and advisor names and roles.'),
            ],
            'feasibility' => [
                'label' => lang_text('Tính khả thi kỹ thuật', 'Technical feasibility'),
                'description' => lang_text('Khối tóm lược chỉ tiêu kỹ thuật, QR theo mã lô, lưu dữ liệu và an toàn vận hành.', 'Section summarizing technical targets, lot-level QR, data retention, and operational safety.'),
            ],
            'contact' => [
                'label' => lang_text('Liên hệ', 'Contact'),
                'description' => lang_text('Thông tin liên hệ và CTA hợp tác.', 'Contact information and partnership CTA.'),
            ],
            'auth_login' => [
                'label' => lang_text('Trang đăng nhập', 'Login page'),
                'description' => lang_text('Nội dung giới thiệu cho người dùng quay lại hệ thống.', 'Showcase copy for returning users signing in.'),
            ],
            'auth_register' => [
                'label' => lang_text('Trang đăng ký', 'Registration page'),
                'description' => lang_text('Nội dung onboarding cho tài khoản mới.', 'Onboarding copy for new account registration.'),
            ],
            'admin_dashboard' => [
                'label' => lang_text('Dashboard admin', 'Admin dashboard'),
                'description' => lang_text('Tiêu đề và mô tả khu điều hành quản trị.', 'Title and intro copy for the admin workspace.'),
            ],
            'partner_dashboard' => [
                'label' => lang_text('Dashboard đối tác', 'Partner dashboard'),
                'description' => lang_text('Nội dung mở đầu khu tạo lô và vận hành QR.', 'Opening content for lot creation and QR operations.'),
            ],
            'supply_dashboard' => [
                'label' => lang_text('Dashboard chuỗi cung ứng', 'Supply dashboard'),
                'description' => lang_text('Nội dung mở đầu cho vận chuyển, kho và người bán.', 'Opening content for transport, warehouse, and seller roles.'),
            ],
        ];
    }

    public function settingsPage(array $params = []): void
    {
        $this->view('admin.settings', [
            'settings' => Setting::all(),
        ]);
    }

    public function apiKeysPage(array $params = []): void
    {
        $this->view('admin.api_keys', [
            'apiKeys' => ApiKey::listAll(),
        ]);
    }

    public function studioPage(array $params = []): void
    {
        $sectionCatalog = $this->studioSectionCatalog();
        $sectionKey = strtolower(trim((string) $this->request->input('section', 'hero')));
        if (!array_key_exists($sectionKey, $sectionCatalog)) {
            $sectionKey = 'hero';
        }

        $cmsSections = $this->preparedCmsSections();

        $this->view('admin.studio', [
            'sectionCatalog' => $sectionCatalog,
            'selectedSectionKey' => $sectionKey,
            'selectedSectionMeta' => $sectionCatalog[$sectionKey],
            'cmsSections' => $cmsSections,
        ]);
    }

    public function users(array $params = []): void
    {
        $hasVipColumn = SchemaSyncService::ensureUserVipColumn();
        $allowedRoles = ['admin', 'partner', 'farmer', 'transporter', 'warehouse', 'seller', 'visitor'];
        $allowedStatuses = ['active', 'pending', 'suspended'];

        $keyword = trim((string) $this->request->input('q', ''));
        if ($keyword !== '') {
            $keyword = function_exists('mb_substr') ? mb_substr($keyword, 0, 120) : substr($keyword, 0, 120);
        }

        $role = strtolower(trim((string) $this->request->input('role', 'all')));
        if ($role !== 'all' && !in_array($role, $allowedRoles, true)) {
            $role = 'all';
        }

        $status = strtolower(trim((string) $this->request->input('status', 'all')));
        if ($status !== 'all' && !in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $page = (int) $this->request->input('page', 1);
        if ($page <= 0) {
            $page = 1;
        }

        $perPage = (int) $this->request->input('per_page', 25);
        if ($perPage <= 0) {
            $perPage = 25;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $where = [];
        $paramsSql = [];

        if ($role !== 'all') {
            $where[] = 'u.role = :role';
            $paramsSql['role'] = $role;
        }

        if ($status !== 'all') {
            $where[] = 'u.status = :status';
            $paramsSql['status'] = $status;
        }

        if ($keyword !== '') {
            $where[] = '(u.full_name LIKE :keyword OR u.email LIKE :keyword OR p.organization_name LIKE :keyword)';
            $paramsSql['keyword'] = '%' . $keyword . '%';
        }

        $fromSql = ' FROM users u
                     LEFT JOIN partner_profiles p ON p.user_id = u.id';
        $whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = Database::pdo()->prepare('SELECT COUNT(*)' . $fromSql . $whereSql);
        foreach ($paramsSql as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $vipSelectSql = $hasVipColumn ? 'u.vip_until,' : 'NULL AS vip_until,';
        $sql = 'SELECT
                    u.id,
                    u.full_name,
                    u.email,
                    u.role,
                    u.status,
                    ' . $vipSelectSql . '
                    u.created_at,
                    u.updated_at,
                    p.organization_name,
                    p.representative_name,
                    p.phone
                ' . $fromSql . $whereSql . '
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset';

        $stmt = Database::pdo()->prepare($sql);
        foreach ($paramsSql as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $this->view('admin.users', [
            'users' => $rows,
            'filters' => [
                'q' => $keyword,
                'role' => $role,
                'status' => $status,
                'per_page' => $perPage,
            ],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'allowedRoles' => $allowedRoles,
            'allowedStatuses' => $allowedStatuses,
            'hasVipColumn' => $hasVipColumn,
        ]);
    }

    public function blog(array $params = []): void
    {
        $q = trim((string) $this->request->input('q', ''));
        $status = strtolower(trim((string) $this->request->input('status', 'all')));
        if (!in_array($status, ['all', 'draft', 'published'], true)) {
            $status = 'all';
        }

        $page = (int) $this->request->input('page', 1);
        if ($page <= 0) {
            $page = 1;
        }

        $perPage = (int) $this->request->input('per_page', 20);
        if (!in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $result = BlogPost::adminList([
            'q' => $q,
            'status' => $status,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $query = http_build_query([
            'q' => $q,
            'status' => $status,
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ]);

        $this->view('admin.blog_manage', [
            'posts' => $result['rows'],
            'filters' => [
                'q' => $q,
                'status' => $status,
                'per_page' => $result['per_page'],
            ],
            'pagination' => [
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
                'total_pages' => $result['total_pages'],
            ],
            'returnTo' => '/dashboard/admin/blog' . ($query !== '' ? '?' . $query : ''),
            'locale' => lang(),
        ]);
    }

    public function trace(array $params = []): void
    {
        $pendingActors = User::pendingActors();
        $actors = User::listByRoles(['farmer', 'partner', 'transporter', 'warehouse', 'seller']);
        $assignments = TraceAssignment::allDetailed();
        $events = TraceEvent::listAllDetailed(200);
        $traceInfo = '';

        $lots = Lot::allWithPartner();
        $packages = LotPackage::allWithLot();

        if (($assignments === [] || $events === []) && ($lots !== [] || $packages !== [])) {
            $seeded = TraceBootstrapService::backfillCreationEvents((int) (current_user()['id'] ?? 1));
            $assignments = TraceAssignment::allDetailed();
            $events = TraceEvent::listAllDetailed(200);

            if ($seeded > 0 || $assignments !== []) {
                $traceInfo = lang_text('Dữ liệu truy xuất đã được khởi tạo từ các lô hàng và gói hàng hiện có.', 'Trace data has been bootstrapped from existing lots and packages.');
            }
        }

        $pendingRole = strtolower(trim((string) $this->request->input('pending_role', 'all')));
        if ($pendingRole !== 'all' && !in_array($pendingRole, ['partner', 'farmer', 'transporter', 'warehouse', 'seller'], true)) {
            $pendingRole = 'all';
        }

        $pendingKeyword = trim((string) $this->request->input('pending_q', ''));
        if ($pendingKeyword !== '') {
            $pendingKeyword = function_exists('mb_substr') ? mb_substr($pendingKeyword, 0, 120) : substr($pendingKeyword, 0, 120);
        }

        $pendingFiltered = array_values(array_filter($pendingActors, static function (array $row) use ($pendingRole, $pendingKeyword): bool {
            if ($pendingRole !== 'all' && (string) ($row['role'] ?? '') !== $pendingRole) {
                return false;
            }

            if ($pendingKeyword === '') {
                return true;
            }

            $haystacks = [
                (string) ($row['full_name'] ?? ''),
                (string) ($row['email'] ?? ''),
                (string) ($row['organization_name'] ?? ''),
            ];

            return self::arrayMatchesKeyword($haystacks, $pendingKeyword);
        }));
        $pendingPerPage = $this->tracePerPage('pending_per_page', 10);
        $pendingPage = $this->tracePage('pending_page');
        $pendingPagination = $this->paginateRows($pendingFiltered, $pendingPage, $pendingPerPage);

        $assignmentEntityType = strtolower(trim((string) $this->request->input('assignment_entity_type', 'all')));
        if (!in_array($assignmentEntityType, ['all', 'lot', 'package'], true)) {
            $assignmentEntityType = 'all';
        }

        $assignmentActorRole = strtolower(trim((string) $this->request->input('assignment_actor_role', 'all')));
        if (!in_array($assignmentActorRole, ['all', 'partner', 'farmer', 'transporter', 'warehouse', 'seller'], true)) {
            $assignmentActorRole = 'all';
        }

        $assignmentKeyword = trim((string) $this->request->input('assignment_q', ''));
        if ($assignmentKeyword !== '') {
            $assignmentKeyword = function_exists('mb_substr') ? mb_substr($assignmentKeyword, 0, 120) : substr($assignmentKeyword, 0, 120);
        }

        $assignmentFiltered = array_values(array_filter($assignments, static function (array $row) use ($assignmentEntityType, $assignmentActorRole, $assignmentKeyword): bool {
            if ($assignmentEntityType !== 'all' && (string) ($row['entity_type'] ?? '') !== $assignmentEntityType) {
                return false;
            }

            if ($assignmentActorRole !== 'all' && (string) ($row['actor_role'] ?? '') !== $assignmentActorRole) {
                return false;
            }

            if ($assignmentKeyword === '') {
                return true;
            }

            $haystacks = [
                (string) ($row['stage_code'] ?? ''),
                (string) ($row['actor_name'] ?? ''),
                (string) ($row['actor_email'] ?? ''),
                (string) ($row['assigned_by_name'] ?? ''),
                (string) ($row['lot_code'] ?? ''),
                (string) ($row['package_code'] ?? ''),
                (string) ($row['entity_id'] ?? ''),
            ];

            return self::arrayMatchesKeyword($haystacks, $assignmentKeyword);
        }));
        $assignmentPerPage = $this->tracePerPage('assignment_per_page', 10);
        $assignmentPage = $this->tracePage('assignment_page');
        $assignmentPagination = $this->paginateRows($assignmentFiltered, $assignmentPage, $assignmentPerPage);

        $eventEntityType = strtolower(trim((string) $this->request->input('event_entity_type', 'all')));
        if (!in_array($eventEntityType, ['all', 'lot', 'package'], true)) {
            $eventEntityType = 'all';
        }

        $eventActorRole = strtolower(trim((string) $this->request->input('event_actor_role', 'all')));
        if (!in_array($eventActorRole, ['all', 'admin', 'partner', 'farmer', 'transporter', 'warehouse', 'seller'], true)) {
            $eventActorRole = 'all';
        }

        $eventKeyword = trim((string) $this->request->input('event_q', ''));
        if ($eventKeyword !== '') {
            $eventKeyword = function_exists('mb_substr') ? mb_substr($eventKeyword, 0, 120) : substr($eventKeyword, 0, 120);
        }

        $eventFiltered = array_values(array_filter($events, static function (array $row) use ($eventEntityType, $eventActorRole, $eventKeyword): bool {
            if ($eventEntityType !== 'all' && (string) ($row['entity_type'] ?? '') !== $eventEntityType) {
                return false;
            }

            if ($eventActorRole !== 'all' && (string) ($row['actor_role'] ?? '') !== $eventActorRole) {
                return false;
            }

            if ($eventKeyword === '') {
                return true;
            }

            $haystacks = [
                (string) ($row['stage_code'] ?? ''),
                (string) ($row['location_name'] ?? ''),
                (string) ($row['actor_name'] ?? ''),
                (string) ($row['lot_code'] ?? ''),
                (string) ($row['package_code'] ?? ''),
                (string) ($row['entity_id'] ?? ''),
            ];

            return self::arrayMatchesKeyword($haystacks, $eventKeyword);
        }));
        $eventPerPage = $this->tracePerPage('event_per_page', 10);
        $eventPage = $this->tracePage('event_page');
        $eventPagination = $this->paginateRows($eventFiltered, $eventPage, $eventPerPage);

        $this->view('admin.trace', [
            'pendingActors' => $pendingPagination['rows'],
            'actors' => $actors,
            'assignments' => $assignmentPagination['rows'],
            'events' => $eventPagination['rows'],
            'lots' => $lots,
            'packages' => $packages,
            'traceInfo' => $traceInfo,
            'pendingFilters' => [
                'q' => $pendingKeyword,
                'role' => $pendingRole,
                'per_page' => $pendingPerPage,
            ],
            'pendingPagination' => $pendingPagination['meta'],
            'assignmentFilters' => [
                'q' => $assignmentKeyword,
                'entity_type' => $assignmentEntityType,
                'actor_role' => $assignmentActorRole,
                'per_page' => $assignmentPerPage,
            ],
            'assignmentPagination' => $assignmentPagination['meta'],
            'eventFilters' => [
                'q' => $eventKeyword,
                'entity_type' => $eventEntityType,
                'actor_role' => $eventActorRole,
                'per_page' => $eventPerPage,
            ],
            'eventPagination' => $eventPagination['meta'],
        ]);
    }

    private function tracePerPage(string $key, int $default = 10): int
    {
        $perPage = (int) $this->request->input($key, $default);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            return $default;
        }

        return $perPage;
    }

    private function tracePage(string $key): int
    {
        $page = (int) $this->request->input($key, 1);
        return $page > 0 ? $page : 1;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array{rows: array<int,array<string,mixed>>, meta: array<string,int>}
     */
    private function paginateRows(array $rows, int $page, int $perPage): array
    {
        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;

        return [
            'rows' => array_slice($rows, $offset, $perPage),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    /**
     * @param array<int,string> $haystacks
     */
    private static function arrayMatchesKeyword(array $haystacks, string $keyword): bool
    {
        $needle = trim($keyword);
        if ($needle === '') {
            return true;
        }

        foreach ($haystacks as $value) {
            if ($value !== '' && stripos($value, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    public function approvePartner(array $params): void
    {
        $this->updateUserStatus($params);
    }

    public function updateUserStatus(array $params): void
    {
        $redirect = $this->sanitizeAdminRedirect((string) $this->request->input('_redirect', '/dashboard/admin/users'));
        $id = (int) ($params['id'] ?? 0);
        $status = strtolower(trim((string) $this->request->input('status', '')));

        if ($id <= 0 || User::findById($id) === null) {
            flash('error', lang_text('Không tìm thấy người dùng.', 'User not found.'));
            $this->redirect($redirect);
        }

        if (!in_array($status, ['active', 'suspended', 'pending'], true)) {
            flash('error', lang_text('Trạng thái người dùng không hợp lệ.', 'Invalid user status.'));
            $this->redirect($redirect);
        }

        User::setStatus($id, $status);
        AuditService::log('set_status', 'users', $id, ['status' => $status]);

        flash('success', lang_text('Đã cập nhật trạng thái người dùng.', 'User status updated.'));
        $this->redirect($redirect);
    }

    public function updateUserVip(array $params): void
    {
        $redirect = $this->sanitizeAdminRedirect((string) $this->request->input('_redirect', '/dashboard/admin/users'));
        $id = (int) ($params['id'] ?? 0);
        $user = $id > 0 ? User::findById($id) : null;

        if ($user === null) {
            flash('error', lang_text('Không tìm thấy người dùng.', 'User not found.'));
            $this->redirect($redirect);
        }

        if (!SchemaSyncService::ensureUserVipColumn()) {
            flash('error', lang_text('Không thể kích hoạt cột mốc truy cập mở rộng trong cơ sở dữ liệu.', 'Unable to enable the extended-access marker column in the database.'));
            $this->redirect($redirect);
        }

        $vipInput = trim((string) $this->request->input('vip_until', ''));
        if ($vipInput === '') {
            User::setVipUntil($id, null);
            AuditService::log('clear_vip_until', 'users', $id, []);
            flash('success', lang_text('Đã gỡ mốc truy cập mở rộng của người dùng.', 'The user extended-access marker has been cleared.'));
            $this->redirect($redirect);
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $vipInput . ' 23:59:59');
        if (!$date instanceof \DateTimeImmutable) {
            flash('error', lang_text('Ngày kích hoạt mở rộng không hợp lệ.', 'Invalid extended-access date.'));
            $this->redirect($redirect);
        }

        $today = new \DateTimeImmutable('today');
        if ($date < $today) {
            flash('error', lang_text('Ngày kích hoạt mở rộng phải từ hôm nay trở đi.', 'The extended-access date must be today or later.'));
            $this->redirect($redirect);
        }

        $vipUntil = $date->format('Y-m-d H:i:s');
        User::setVipUntil($id, $vipUntil);
        AuditService::log('set_vip_until', 'users', $id, ['vip_until' => $vipUntil]);

        flash('success', lang_text('Đã cập nhật mốc truy cập mở rộng.', 'Extended-access marker updated.'));
        $this->redirect($redirect);
    }

    private function sanitizeAdminRedirect(string $redirect, string $fallback = '/dashboard/admin/users'): string
    {
        $redirect = trim($redirect);
        if ($redirect === '') {
            return $fallback;
        }

        if (str_contains($redirect, "\n") || str_contains($redirect, "\r")) {
            return $fallback;
        }

        if (preg_match('#^/dashboard/admin(?:/|$|[?#])#', $redirect) !== 1) {
            return $fallback;
        }

        return $redirect;
    }

    public function saveTraceAssignment(array $params = []): void
    {
        $entityType = TraceAssignmentService::normalizeEntityType((string) $this->request->input('entity_type', ''));
        $entityId = (int) $this->request->input('entity_id', 0);
        $stageCode = TraceAssignmentService::normalizeStageCode((string) $this->request->input('stage_code', ''));
        $actorUserId = (int) $this->request->input('actor_user_id', 0);

        if ($entityType === null || $entityId <= 0 || $actorUserId <= 0) {
            flash('error', lang_text('Thông tin phân công không hợp lệ.', 'Invalid assignment payload.'));
            $this->redirect('/dashboard/admin/trace');
        }

        if (!TraceAssignmentService::entityExists($entityType, $entityId)) {
            flash('error', lang_text('Thực thể được chỉ định không tồn tại.', 'Assigned entity does not exist.'));
            $this->redirect('/dashboard/admin/trace');
        }

        TraceAssignment::upsert(
            $entityType,
            $entityId,
            $stageCode,
            $actorUserId,
            (int) (current_user()['id'] ?? 0)
        );

        AuditService::log('create', 'trace_assignments', null, [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'stage_code' => $stageCode,
            'actor_user_id' => $actorUserId,
        ]);
        flash('success', lang_text('Đã lưu phân công truy xuất.', 'Trace assignment saved.'));
        $this->redirect('/dashboard/admin/trace');
    }

    public function deleteTraceAssignment(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        TraceAssignment::deleteById($id);
        AuditService::log('delete', 'trace_assignments', $id, []);

        flash('success', lang_text('Đã xóa phân công truy xuất.', 'Assignment deleted.'));
        $this->redirect('/dashboard/admin/trace');
    }

    public function updateSettings(array $params = []): void
    {
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
            $value = (string) $this->request->input($key, '');
            if ($key === 'auto_publish_lot') {
                $value = $value === '1' ? '1' : '0';
            }
            Setting::set($key, $value);
        }

        AuditService::log('update', 'admin_settings', null, ['keys' => $keys]);

        flash('success', lang_text('Đã lưu cài đặt hệ thống.', 'System settings saved.'));
        $this->redirect('/dashboard/admin/settings');
    }

    public function updateCms(array $params): void
    {
        $section = (string) ($params['section_key'] ?? '');
        $redirect = '/dashboard/admin/studio?section=' . rawurlencode($section !== '' ? $section : 'hero');
        if ($section === '') {
            flash('error', lang_text('Thiếu khóa phần.', 'Missing section key.'));
            $this->redirect('/dashboard/admin/studio?section=hero');
        }

        $titleVi = trim((string) $this->request->input('title_vi', ''));
        $bodyVi = trim((string) $this->request->input('body_vi', ''));
        $imagePath = null;

        try {
            $imagePath = UploadService::image($this->request->file('image'));
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect($redirect);
        }

        $existingVi = CmsSection::getContent($section, 'vi');
        $vi = [
            'title' => $titleVi !== '' ? $titleVi : (string) ($existingVi['title'] ?? ''),
            'body' => $bodyVi !== '' ? $bodyVi : (string) ($existingVi['body'] ?? ''),
            'image' => $imagePath ?? ($existingVi['image'] ?? null),
        ];
        $en = TranslateService::structuredToEnglish($vi);

        CmsSection::save($section, $vi, $en);
        AuditService::log('update', 'cms_sections', null, ['section' => $section]);

        flash('success', lang_text('Phần CMS được cập nhật:', 'CMS section updated: ') . $section);
        $this->redirect($redirect);
    }

    public function saveBlog(array $params = []): void
    {
        $redirect = $this->sanitizeAdminRedirect(
            (string) $this->request->input('_redirect', '/dashboard/admin/blog'),
            '/dashboard/admin/blog'
        );

        $input = $this->request->all();
        $id = (int) $this->request->input('id', 0);
        $titleVi = trim((string) $this->request->input('title_vi', ''));
        $contentVi = trim((string) $this->request->input('content_vi', ''));

        $errors = Validator::required(
            ['title_vi' => $titleVi, 'content_vi' => $contentVi],
            ['title_vi', 'content_vi']
        );

        if ($errors) {
            flash('error', lang_text('Tải trọng blog không đầy đủ.', 'Blog payload is incomplete.'));
            $this->redirect($redirect);
        }

        $existing = null;
        if ($id > 0) {
            $existing = BlogPost::find($id);
            if ($existing === null) {
                flash('error', lang_text('Không tìm thấy bài đăng trên blog.', 'Blog post was not found.'));
                $this->redirect($redirect);
            }
        }

        $titleEn = TranslateService::toEnglish($titleVi);
        $contentEn = TranslateService::toEnglish($contentVi);

        $status = (string) $this->request->input('status', $existing['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published'], true)) {
            $status = 'draft';
        }

        $excerptSourceVi = trim(strip_tags($contentVi)) !== '' ? trim(strip_tags($contentVi)) : $titleVi;
        $autoExcerptVi = mb_substr($excerptSourceVi, 0, 160);
        $autoSeoDescription = mb_substr($excerptSourceVi, 0, 200);

        $excerptVi = trim((string) $this->request->input('excerpt_vi', $autoExcerptVi));
        $seoTitle = trim((string) $this->request->input('seo_title', $titleVi));
        $seoDescription = trim((string) $this->request->input('seo_description', $autoSeoDescription));

        if ($id > 0 && $existing !== null) {
            if (array_key_exists('excerpt_vi', $input) && trim((string) $input['excerpt_vi']) === '') {
                $excerptVi = $autoExcerptVi;
            }
            if (!array_key_exists('seo_title', $input)) {
                $seoTitle = (string) ($existing['seo_title'] ?? '');
            }
            if (!array_key_exists('seo_description', $input)) {
                $seoDescription = (string) ($existing['seo_description'] ?? '');
            }
        }

        $excerptEn = TranslateService::toEnglish($excerptVi);

        $slugInput = trim((string) $this->request->input('slug', ''));
        $slugSource = $slugInput !== ''
            ? $slugInput
            : (string) ($existing['slug'] ?? $titleVi);

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
            flash('error', $e->getMessage());
            $this->redirect($redirect);
        }

        if ($id > 0) {
            BlogPost::update($id, $data);
            AuditService::log('update', 'blog_posts', $id, []);
        } else {
            $newId = BlogPost::create($data);
            AuditService::log('create', 'blog_posts', $newId, []);
        }

        flash('success', lang_text('Đã lưu bài đăng trên blog.', 'Blog post saved.'));
        $this->redirect($redirect);
    }

    public function createApiKey(array $params = []): void
    {
        $name = trim((string) $this->request->input('name', 'Machine Integration'));
        $plainKey = 'agri_' . bin2hex(random_bytes(20));
        ApiKey::create($name, $plainKey);

        AuditService::log('create', 'api_keys', null, ['name' => $name]);

        flash(
            'success',
            lang_text(
                'khóa API tạo:' . $plainKey . '(hãy lưu ngay, key chỉ hiển thị một lần).',
                'API key created: ' . $plainKey . ' (store it now, it is shown once).'
            )
        );
        $this->redirect('/dashboard/admin/api-keys');
    }

    public function setLotPublish(array $params): void
    {
        $lotId = (int) ($params['id'] ?? 0);
        $status = (string) $this->request->input('publish_status', 'draft');
        if (!in_array($status, ['draft', 'published'], true)) {
            $status = 'draft';
        }

        Lot::setPublishStatus($lotId, $status);
        AuditService::log('set_publish_status', 'lots', $lotId, ['status' => $status]);

        flash('success', lang_text('Đã cập nhật trạng thái xuất bản lô.', 'Lot publish status updated.'));
        $this->redirect('/dashboard/admin#lots');
    }
}

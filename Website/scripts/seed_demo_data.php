<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Core\Env;
use App\Models\PartnerProfile;
use App\Models\TraceAssignment;
use App\Models\TraceEvent;
use App\Services\QrService;

date_default_timezone_set('Asia/Ho_Chi_Minh');

define('BASE_PATH', dirname(__DIR__));

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} else {
    require BASE_PATH . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
}

require BASE_PATH . '/app/Core/functions.php';

Env::load(BASE_PATH . '/.env');
Config::setAll(require BASE_PATH . '/config/app.php');
Database::init();

$pdo = Database::pdo();
$password = 'Admin@123456';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$imageDir = BASE_PATH . '/public/uploads/demo';
ensureDirectory($imageDir);

$imageFixtures = [
    'mango' => [
        'filename' => 'mango-demo.jpg',
        'relative_path' => 'public/uploads/demo/mango-demo.jpg',
        'download_url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/File:Mangoes%20%2C%2C.jpg',
        'source_page' => 'https://commons.wikimedia.org/wiki/File:Mangoes_,,.jpg',
        'credit' => 'Mangoes ,, (Wikimedia Commons, CC BY-SA 4.0)',
    ],
    'avocado' => [
        'filename' => 'avocado-demo.jpg',
        'relative_path' => 'public/uploads/demo/avocado-demo.jpg',
        'download_url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/File:Avocado%20fruitnfoliage.jpg',
        'source_page' => 'https://commons.wikimedia.org/wiki/File:Avocado_fruitnfoliage.jpg',
        'credit' => 'Avocado fruitnfoliage (Wikimedia Commons, CC BY 1.0)',
    ],
    'dragonfruit' => [
        'filename' => 'dragonfruit-demo.jpg',
        'relative_path' => 'public/uploads/demo/dragonfruit-demo.jpg',
        'download_url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/File:Dragonfruit%20Chiayi%20market.jpg',
        'source_page' => 'https://commons.wikimedia.org/wiki/File:Dragonfruit_Chiayi_market.jpg',
        'credit' => 'Dragonfruit Chiayi market (Wikimedia Commons, CC BY-SA 2.0)',
    ],
    'orange' => [
        'filename' => 'orange-demo.jpg',
        'relative_path' => 'public/uploads/demo/orange-demo.jpg',
        'download_url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/File:Citrus%20sinensis.jpg',
        'source_page' => 'https://commons.wikimedia.org/wiki/File:Citrus_sinensis.jpg',
        'credit' => 'Citrus sinensis (Wikimedia Commons, public domain)',
    ],
    'banana' => [
        'filename' => 'banana-demo.jpg',
        'relative_path' => 'public/uploads/demo/banana-demo.jpg',
        'download_url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/File:Bananas%20fruit%20%281%29.jpg',
        'source_page' => 'https://commons.wikimedia.org/wiki/File:Bananas_fruit_(1).jpg',
        'credit' => 'Bananas fruit (1) (Wikimedia Commons, public domain)',
    ],
];

$downloaded = [];
foreach ($imageFixtures as $key => $fixture) {
    $targetPath = $imageDir . '/' . $fixture['filename'];
    downloadIfMissing((string) $fixture['download_url'], $targetPath);
    if (!is_file($targetPath) || (int) filesize($targetPath) <= 0) {
        throw new RuntimeException('Cannot prepare demo image: ' . $fixture['filename']);
    }
    $downloaded[$key] = $fixture['relative_path'];
}

$users = [
    'admin' => [
        'full_name' => 'Demo Admin',
        'email' => 'agrisort.demo.admin@gmail.com',
        'role' => 'admin',
        'status' => 'active',
    ],
    'partner' => [
        'full_name' => 'Demo Partner',
        'email' => 'agrisort.demo.partner@gmail.com',
        'role' => 'partner',
        'status' => 'active',
        'profile' => [
            'organization_name' => 'Demo Fruit Cooperative',
            'representative_name' => 'Demo Partner',
            'phone' => '0901000001',
            'address' => 'Dong Thap',
            'region' => 'Dong Thap',
            'tax_code' => 'DEMO-PARTNER-001',
        ],
    ],
    'farmer' => [
        'full_name' => 'Demo Farmer',
        'email' => 'agrisort.demo.farmer@gmail.com',
        'role' => 'farmer',
        'status' => 'active',
        'profile' => [
            'organization_name' => 'Demo Highland Farm',
            'representative_name' => 'Demo Farmer',
            'phone' => '0901000002',
            'address' => 'Lam Dong',
            'region' => 'Lam Dong',
            'tax_code' => 'DEMO-FARMER-001',
        ],
    ],
    'transporter' => [
        'full_name' => 'Demo Transporter',
        'email' => 'agrisort.demo.transport@gmail.com',
        'role' => 'transporter',
        'status' => 'active',
    ],
    'warehouse' => [
        'full_name' => 'Demo Warehouse',
        'email' => 'agrisort.demo.warehouse@gmail.com',
        'role' => 'warehouse',
        'status' => 'active',
    ],
    'seller' => [
        'full_name' => 'Demo Seller',
        'email' => 'agrisort.demo.seller@gmail.com',
        'role' => 'seller',
        'status' => 'active',
    ],
];

$userIds = [];

$pdo->beginTransaction();

try {
    foreach ($users as $key => $user) {
        $userId = upsertUser(
            $pdo,
            (string) $user['full_name'],
            (string) $user['email'],
            $passwordHash,
            (string) $user['role'],
            (string) $user['status']
        );
        $userIds[$key] = $userId;

        if (isset($user['profile']) && is_array($user['profile'])) {
            PartnerProfile::upsert($userId, $user['profile']);
        }
    }

    $lots = [
        [
            'owner' => 'partner',
            'lot_code' => 'DEMO-MANGO-2026-01',
            'produce_type' => 'Mango Cat Chu',
            'origin_region' => 'Dong Thap',
            'harvest_date' => '2026-03-04',
            'grade1_count' => 120,
            'grade2_count' => 32,
            'defect_count' => 6,
            'notes' => 'Demo lot with sample image and QR for testing.',
            'image_path' => $downloaded['mango'],
            'publish_status' => 'published',
            'qr_token' => 'demo-lot-mango-2026',
            'package' => [
                'package_code' => 'PKG-MANGO-01',
                'package_label' => 'Mango export carton',
                'quantity' => 24,
                'net_weight_kg' => 12.5,
                'publish_status' => 'published',
                'qr_token' => 'demo-package-mango-2026',
            ],
        ],
        [
            'owner' => 'partner',
            'lot_code' => 'DEMO-AVOCADO-2026-01',
            'produce_type' => 'Avocado 034',
            'origin_region' => 'Lam Dong',
            'harvest_date' => '2026-03-05',
            'grade1_count' => 86,
            'grade2_count' => 22,
            'defect_count' => 4,
            'notes' => 'Sample lot for partner dashboard and trace flow.',
            'image_path' => $downloaded['avocado'],
            'publish_status' => 'published',
            'qr_token' => 'demo-lot-avocado-2026',
            'package' => [
                'package_code' => 'PKG-AVOCADO-01',
                'package_label' => 'Avocado retail box',
                'quantity' => 18,
                'net_weight_kg' => 9.2,
                'publish_status' => 'published',
                'qr_token' => 'demo-package-avocado-2026',
            ],
        ],
        [
            'owner' => 'partner',
            'lot_code' => 'DEMO-DRAGON-2026-01',
            'produce_type' => 'Dragon Fruit',
            'origin_region' => 'Binh Thuan',
            'harvest_date' => '2026-03-06',
            'grade1_count' => 140,
            'grade2_count' => 20,
            'defect_count' => 8,
            'notes' => 'Sample lot with colorful product image.',
            'image_path' => $downloaded['dragonfruit'],
            'publish_status' => 'published',
            'qr_token' => 'demo-lot-dragonfruit-2026',
            'package' => [
                'package_code' => 'PKG-DRAGON-01',
                'package_label' => 'Dragon fruit crate',
                'quantity' => 30,
                'net_weight_kg' => 15.3,
                'publish_status' => 'published',
                'qr_token' => 'demo-package-dragonfruit-2026',
            ],
        ],
        [
            'owner' => 'partner',
            'lot_code' => 'DEMO-ORANGE-2026-01',
            'produce_type' => 'Orange Sanh',
            'origin_region' => 'Vinh Long',
            'harvest_date' => '2026-03-07',
            'grade1_count' => 104,
            'grade2_count' => 18,
            'defect_count' => 5,
            'notes' => 'Orange example for homepage featured lots.',
            'image_path' => $downloaded['orange'],
            'publish_status' => 'published',
            'qr_token' => 'demo-lot-orange-2026',
            'package' => [
                'package_code' => 'PKG-ORANGE-01',
                'package_label' => 'Orange family pack',
                'quantity' => 20,
                'net_weight_kg' => 10.8,
                'publish_status' => 'published',
                'qr_token' => 'demo-package-orange-2026',
            ],
        ],
        [
            'owner' => 'farmer',
            'lot_code' => 'DEMO-BANANA-2026-01',
            'produce_type' => 'Banana Nam My',
            'origin_region' => 'Dong Nai',
            'harvest_date' => '2026-03-03',
            'grade1_count' => 96,
            'grade2_count' => 14,
            'defect_count' => 3,
            'notes' => 'Farmer-owned demo lot for account testing.',
            'image_path' => $downloaded['banana'],
            'publish_status' => 'published',
            'qr_token' => 'demo-lot-banana-2026',
            'package' => [
                'package_code' => 'PKG-BANANA-01',
                'package_label' => 'Banana fresh box',
                'quantity' => 16,
                'net_weight_kg' => 11.4,
                'publish_status' => 'published',
                'qr_token' => 'demo-package-banana-2026',
            ],
        ],
    ];

    $lotIds = [];
    $packageIds = [];
    foreach ($lots as $lot) {
        $ownerKey = (string) $lot['owner'];
        $ownerId = (int) ($userIds[$ownerKey] ?? 0);
        if ($ownerId <= 0) {
            throw new RuntimeException('Missing owner for lot ' . $lot['lot_code']);
        }

        $lotId = upsertLot($pdo, $ownerId, $lot);
        $lotIds[$lot['lot_code']] = $lotId;

        TraceAssignment::upsert('lot', $lotId, 'lot-created', $ownerId, $ownerId);
        ensureTraceEvent(
            $pdo,
            'lot',
            $lotId,
            'lot-created',
            $ownerId,
            $lot['harvest_date'] . ' 08:00:00',
            (string) $lot['origin_region'],
            'Demo seed: initial lot registration.'
        );

        QrService::ensureStoredImage((string) $lot['qr_token'], 'png');
        QrService::ensureStoredImage((string) $lot['qr_token'], 'svg');

        $package = is_array($lot['package']) ? $lot['package'] : [];
        $packageId = upsertPackage($pdo, $lotId, $ownerId, $package);
        $packageIds[$package['package_code']] = $packageId;

        TraceAssignment::upsert('package', $packageId, 'package-created', $ownerId, $ownerId);
        ensureTraceEvent(
            $pdo,
            'package',
            $packageId,
            'package-created',
            $ownerId,
            $lot['harvest_date'] . ' 11:00:00',
            (string) $lot['origin_region'],
            'Demo seed: package created from sample lot.'
        );

        QrService::ensureStoredImage((string) $package['qr_token'], 'png');
        QrService::ensureStoredImage((string) $package['qr_token'], 'svg');
    }

    $transportPackageId = (int) ($packageIds['PKG-MANGO-01'] ?? 0);
    if ($transportPackageId > 0) {
        TraceAssignment::upsert('package', $transportPackageId, 'transport-checkin', (int) $userIds['transporter'], (int) $userIds['admin']);
        ensureTraceEvent(
            $pdo,
            'package',
            $transportPackageId,
            'transport-checkin',
            (int) $userIds['transporter'],
            '2026-03-08 07:45:00',
            'Dong Thap sorting point',
            'Demo transporter checked in and accepted shipment.'
        );

        TraceAssignment::upsert('package', $transportPackageId, 'warehouse-received', (int) $userIds['warehouse'], (int) $userIds['admin']);
        ensureTraceEvent(
            $pdo,
            'package',
            $transportPackageId,
            'warehouse-received',
            (int) $userIds['warehouse'],
            '2026-03-08 10:30:00',
            'Binh Duong warehouse',
            'Demo warehouse received and counted the package.'
        );

        TraceAssignment::upsert('package', $transportPackageId, 'retail-stocked', (int) $userIds['seller'], (int) $userIds['admin']);
        ensureTraceEvent(
            $pdo,
            'package',
            $transportPackageId,
            'retail-stocked',
            (int) $userIds['seller'],
            '2026-03-08 16:10:00',
            'Ho Chi Minh City retail shelf',
            'Demo seller stocked the package for public trace view.'
        );
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, '[ERROR] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

writeAttributionFile($imageDir . '/ATTRIBUTION.txt', $imageFixtures);

echo 'Demo accounts seeded.' . PHP_EOL;
echo 'Password for all demo accounts: ' . $password . PHP_EOL;
foreach ($users as $user) {
    echo '- ' . $user['role'] . ': ' . $user['email'] . PHP_EOL;
}
echo 'Demo images ready in public/uploads/demo.' . PHP_EOL;
echo 'Demo lots/packages and QR images are available.' . PHP_EOL;

function ensureDirectory(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Cannot create directory: ' . $path);
    }
}

function downloadIfMissing(string $url, string $targetPath): void
{
    if (is_file($targetPath) && (int) filesize($targetPath) > 0) {
        return;
    }

    $data = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_USERAGENT => 'AgrisortDemoSeeder/1.0',
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if ($response !== false && $status >= 200 && $status < 400) {
                $data = $response;
            }
            curl_close($ch);
        }
    }

    if (!is_string($data) || $data === '') {
        $context = stream_context_create([
            'http' => [
                'timeout' => 45,
                'follow_location' => 1,
                'header' => "User-Agent: AgrisortDemoSeeder/1.0\r\n",
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        if (is_string($response) && $response !== '') {
            $data = $response;
        }
    }

    if (!is_string($data) || $data === '') {
        throw new RuntimeException('Download failed: ' . $url);
    }

    $written = file_put_contents($targetPath, $data, LOCK_EX);
    if ($written === false || $written <= 0) {
        throw new RuntimeException('Cannot save file: ' . $targetPath);
    }
}

function upsertUser(PDO $pdo, string $fullName, string $email, string $passwordHash, string $role, string $status): int
{
    $sql = 'INSERT INTO users (full_name, email, password_hash, role, status, created_at, updated_at)
            VALUES (:full_name, :email, :password_hash, :role, :status, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id),
                full_name = VALUES(full_name),
                password_hash = VALUES(password_hash),
                role = VALUES(role),
                status = VALUES(status),
                updated_at = NOW()';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => $role,
        'status' => $status,
    ]);

    return (int) $pdo->lastInsertId();
}

function upsertLot(PDO $pdo, int $partnerId, array $lot): int
{
    $sql = 'INSERT INTO lots
            (partner_id, lot_code, produce_type, origin_region, harvest_date, grade1_count, grade2_count, defect_count, notes, image_path, publish_status, qr_token, created_at, updated_at)
            VALUES
            (:partner_id, :lot_code, :produce_type, :origin_region, :harvest_date, :grade1_count, :grade2_count, :defect_count, :notes, :image_path, :publish_status, :qr_token, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id),
                produce_type = VALUES(produce_type),
                origin_region = VALUES(origin_region),
                harvest_date = VALUES(harvest_date),
                grade1_count = VALUES(grade1_count),
                grade2_count = VALUES(grade2_count),
                defect_count = VALUES(defect_count),
                notes = VALUES(notes),
                image_path = VALUES(image_path),
                publish_status = VALUES(publish_status),
                qr_token = VALUES(qr_token),
                updated_at = NOW()';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'partner_id' => $partnerId,
        'lot_code' => (string) $lot['lot_code'],
        'produce_type' => (string) $lot['produce_type'],
        'origin_region' => (string) $lot['origin_region'],
        'harvest_date' => (string) $lot['harvest_date'],
        'grade1_count' => (int) $lot['grade1_count'],
        'grade2_count' => (int) $lot['grade2_count'],
        'defect_count' => (int) $lot['defect_count'],
        'notes' => (string) $lot['notes'],
        'image_path' => (string) $lot['image_path'],
        'publish_status' => (string) $lot['publish_status'],
        'qr_token' => (string) $lot['qr_token'],
    ]);

    return (int) $pdo->lastInsertId();
}

function upsertPackage(PDO $pdo, int $lotId, int $createdByUserId, array $package): int
{
    $sql = 'INSERT INTO lot_packages
            (lot_id, package_code, package_label, quantity, net_weight_kg, qr_token, publish_status, created_by_user_id, created_at, updated_at)
            VALUES
            (:lot_id, :package_code, :package_label, :quantity, :net_weight_kg, :qr_token, :publish_status, :created_by_user_id, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id),
                package_label = VALUES(package_label),
                quantity = VALUES(quantity),
                net_weight_kg = VALUES(net_weight_kg),
                qr_token = VALUES(qr_token),
                publish_status = VALUES(publish_status),
                created_by_user_id = VALUES(created_by_user_id),
                updated_at = NOW()';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'lot_id' => $lotId,
        'package_code' => (string) $package['package_code'],
        'package_label' => (string) $package['package_label'],
        'quantity' => (int) $package['quantity'],
        'net_weight_kg' => (float) $package['net_weight_kg'],
        'qr_token' => (string) $package['qr_token'],
        'publish_status' => (string) $package['publish_status'],
        'created_by_user_id' => $createdByUserId,
    ]);

    return (int) $pdo->lastInsertId();
}

function ensureTraceEvent(
    PDO $pdo,
    string $entityType,
    int $entityId,
    string $stageCode,
    int $actorUserId,
    string $eventTime,
    string $locationName,
    string $note
): void {
    $stmt = $pdo->prepare(
        'SELECT id
         FROM trace_events
         WHERE entity_type = :entity_type
           AND entity_id = :entity_id
           AND stage_code = :stage_code
           AND actor_user_id = :actor_user_id
         LIMIT 1'
    );
    $stmt->execute([
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'stage_code' => $stageCode,
        'actor_user_id' => $actorUserId,
    ]);

    $existingId = (int) $stmt->fetchColumn();
    if ($existingId > 0) {
        $update = $pdo->prepare(
            'UPDATE trace_events
             SET event_time = :event_time,
                 location_name = :location_name,
                 note = :note,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $update->execute([
            'event_time' => $eventTime,
            'location_name' => $locationName,
            'note' => $note,
            'id' => $existingId,
        ]);
        return;
    }

    TraceEvent::create([
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'stage_code' => $stageCode,
        'event_time' => $eventTime,
        'location_name' => $locationName,
        'note' => $note,
        'actor_user_id' => $actorUserId,
    ]);
}

function writeAttributionFile(string $targetPath, array $fixtures): void
{
    $lines = [
        'Demo images downloaded on 2026-03-10',
        'Source pages and licenses:',
    ];

    foreach ($fixtures as $fixture) {
        $lines[] = '- ' . $fixture['filename'] . ': ' . $fixture['credit'];
        $lines[] = '  ' . $fixture['source_page'];
    }

    file_put_contents($targetPath, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX);
}

<?php
declare(strict_types=1);

namespace App\Support;

use App\Services\TranslateService;

final class PublicContent
{
    /**
     * @return array<string, array{vi: array<string, string>, en: array<string, string>}>
     */
    public static function cmsSections(): array
    {
        return [
            'hero' => [
                'vi' => [
                    'title' => 'AGRISORT-AI: Hệ thống phân loại nông sản thông minh tích hợp truy xuất nguồn gốc QR',
                    'body' => implode("\n", [
                        'Máy phân loại nông sản ứng dụng camera, thị giác máy tính và AI để đánh giá màu sắc, kích thước, độ chín và khuyết tật bề mặt.',
                        'Kết quả phân loại được lưu theo mã lô và gắn QR truy xuất nguồn gốc ngay trên cùng dây chuyền.',
                        'Phù hợp với hợp tác xã, trạm thu mua, cơ sở sơ chế và doanh nghiệp nông sản quy mô nhỏ-vừa.',
                    ]),
                ],
                'en' => [
                    'title' => 'AGRISORT-AI: Smart agricultural sorting system with integrated QR traceability',
                    'body' => implode("\n", [
                        'A smart produce-sorting machine that combines industrial cameras, computer vision, and AI to assess color, size, ripeness, and surface defects.',
                        'Sorting results are stored by lot code and linked to QR traceability within the same processing line.',
                        'Designed for cooperatives, collection stations, post-harvest facilities, and small-to-medium agribusinesses.',
                    ]),
                ],
            ],
            'solution' => [
                'vi' => [
                    'title' => 'Giải pháp chuyên biệt cho bài toán nông sản Việt Nam',
                    'body' => implode("\n", [
                        'Giảm phân loại thủ công chủ quan, tốn nhân công và thiếu đồng đều theo lô sau thu hoạch.',
                        'Phù hợp phân khúc HTX, trạm thu mua và cơ sở sơ chế nhỏ-vừa khó đầu tư dây chuyền nhập khẩu.',
                        'Thiết kế gọn, dễ lắp đặt, dễ bảo trì và có thể tinh chỉnh theo từng loại nông sản theo mùa vụ.',
                        'Số hóa kết quả phân loại theo mã lô để thống kê chất lượng, truy vết và hỗ trợ quyết định thu mua.',
                    ]),
                ],
                'en' => [
                    'title' => 'A specialized solution for Vietnamese post-harvest operations',
                    'body' => implode("\n", [
                        'Reduce subjective manual sorting, labor intensity, and inconsistent quality between lots.',
                        'Fit the budget and operating model of cooperatives, buying stations, and small-to-medium processing facilities.',
                        'Compact, maintainable, and adaptable to different produce categories across seasonal harvest cycles.',
                        'Digitize grading outcomes by lot code for quality statistics, traceability, and purchasing decisions.',
                    ]),
                ],
            ],
            'technology' => [
                'vi' => [
                    'title' => 'Pipeline xử lý và điều khiển thời gian thực',
                    'body' => implode("\n", [
                        'Pipeline xử lý: Camera -> Xử lý ảnh -> Mô hình AI -> Quyết định phân loại -> Cơ cấu gạt.',
                        'Đồng bộ quyết định AI với vị trí sản phẩm trên băng tải để gạt đúng Loại 1, Loại 2 và Lỗi.',
                        'Mục tiêu thời gian ra quyết định <= 200 ms để đáp ứng vận hành liên tục trong mùa vụ cao điểm.',
                    ]),
                ],
                'en' => [
                    'title' => 'Processing pipeline and real-time control',
                    'body' => implode("\n", [
                        'Processing flow: Camera -> Image Processing -> AI Model -> Sorting Decision -> Diverter Actuator.',
                        'AI output is synchronized with conveyor position so each fruit is diverted into the correct grade channel.',
                        'Target decision latency is <= 200 ms to sustain continuous operation during peak harvest periods.',
                    ]),
                ],
            ],
            'impact' => [
                'vi' => [
                    'title' => 'Hiệu quả kinh tế - xã hội',
                    'body' => implode("\n", [
                        'Độ chính xác thử nghiệm: 93-96% với mục tiêu kỹ thuật tối thiểu >= 90%.',
                        'Năng suất định hướng: 1.000-2.000 quả/giờ tùy loại nông sản và cấu hình triển khai.',
                        'Giảm 50-70% lao động phân loại trực tiếp, tăng độ đồng đều đầu ra và giảm sai sót chủ quan.',
                        'Tăng minh bạch chuỗi cung ứng nhờ dữ liệu theo mã lô, QR truy xuất và thông tin chất lượng đi kèm.',
                    ]),
                ],
                'en' => [
                    'title' => 'Economic and social impact',
                    'body' => implode("\n", [
                        'Pilot accuracy reaches 93-96%, with a baseline technical target of >= 90%.',
                        'Expected throughput is 1,000-2,000 fruits per hour depending on crop type and deployment configuration.',
                        'Reduce direct sorting labor by 50-70% while improving consistency and reducing human subjectivity.',
                        'Strengthen supply-chain transparency through lot-level data, QR traceability, and quality-linked records.',
                    ]),
                ],
            ],
            'swot' => [
                'vi' => [
                    'title' => 'Hệ sinh thái và chuỗi liên kết giá trị',
                    'body' => implode("\n", [
                        'Hộ nông dân và nhà vườn có dữ liệu phân hạng rõ ràng để tăng giá trị nông sản sau thu hoạch.',
                        'HTX và tổ hợp tác chuẩn hóa chất lượng theo mã lô để xây dựng thương hiệu vùng trồng và dễ thương lượng hơn.',
                        'Trạm thu mua, cơ sở sơ chế, doanh nghiệp và siêu thị có đầu vào đồng đều kèm dữ liệu minh bạch để giảm rủi ro trả hàng.',
                        'Chuỗi dữ liệu vận hành xuyên suốt từ băng tải -> AI phân loại -> mã lô -> QR -> phân phối -> người mua cuối.',
                    ]),
                ],
                'en' => [
                    'title' => 'Ecosystem and value-chain integration',
                    'body' => implode("\n", [
                        'Farmers and growers receive clear grading records that support better post-harvest value capture.',
                        'Cooperatives can standardize lot quality, strengthen regional branding, and negotiate with more confidence.',
                        'Buying stations, processors, distributors, and retailers receive consistent inputs with transparent supporting data.',
                        'Operational data flows from conveyor sorting to lot code, QR traceability, distribution, and the end buyer.',
                    ]),
                ],
            ],
            'roadmap' => [
                'vi' => [
                    'title' => 'Lộ trình triển khai và phát triển thị trường',
                    'body' => implode("\n", [
                        '0-6 tháng: pilot tại HTX, trạm thu mua và cơ sở sơ chế để kiểm chứng năng lực vận hành thực tế.',
                        '6-24 tháng: mở rộng danh mục nông sản, chuẩn hóa quy trình triển khai và tiếp cận doanh nghiệp phân phối.',
                        '2-5 năm: tích hợp IoT, phân tích dữ liệu chất lượng theo mùa vụ và mở rộng hợp tác sang thị trường ASEAN.',
                    ]),
                ],
                'en' => [
                    'title' => 'Deployment and market roadmap',
                    'body' => implode("\n", [
                        '0-6 months: run pilots with cooperatives, buying stations, and post-harvest facilities.',
                        '6-24 months: expand supported produce categories, standardize rollout practices, and approach distribution partners.',
                        '2-5 years: integrate IoT, seasonal quality analytics, and expand partnerships across ASEAN markets.',
                    ]),
                ],
            ],
            'team' => [
                'vi' => [
                    'title' => 'Đội ngũ thực hiện',
                    'body' => implode("\n", [
                        'Nguyễn Khắc Tùng Lâm - Team Lead / System Integration',
                        'Nguyễn Đăng Quang - AI & Data Pipeline',
                        'Võ Thị Mỹ - Business & Market Analysis',
                        'Trần Đăng Khoa - Embedded & Control',
                        'Lê Hữu Trăng - Mechanical & Automation',
                        'KS. Vũ Văn Dũng - Advisor',
                        'ThS. Nguyễn Thị Thành - Advisor',
                    ]),
                ],
                'en' => [
                    'title' => 'Project team',
                    'body' => implode("\n", [
                        'Nguyen Khac Tung Lam - Team Lead / System Integration',
                        'Nguyen Dang Quang - AI & Data Pipeline',
                        'Vo Thi My - Business & Market Analysis',
                        'Tran Dang Khoa - Embedded & Control',
                        'Le Huu Trang - Mechanical & Automation',
                        'Vu Van Dung, B.Eng. - Advisor',
                        'Nguyen Thi Thanh, M.A. - Advisor',
                    ]),
                ],
            ],
            'feasibility' => [
                'vi' => [
                    'title' => 'Tính khả thi kỹ thuật',
                    'body' => implode("\n", [
                        'Mục tiêu độ chính xác phân loại >= 90%, kết quả thử nghiệm hiện tại đạt 93-96%.',
                        'Thời gian ra quyết định mục tiêu <= 200 ms để kịp gạt đúng vị trí trên băng tải.',
                        'Năng suất thiết kế hướng tới 1.000-2.000 quả/giờ tùy loại nông sản và cấu hình máy.',
                        'QR được tạo theo mã lô trong <= 5 giây; dữ liệu lưu tối thiểu 12-24 tháng để tra cứu và hậu kiểm.',
                        'Hệ thống hướng tới vận hành an toàn với tủ điện, nút dừng khẩn và cơ chế báo trạng thái hoặc báo lỗi.',
                    ]),
                ],
                'en' => [
                    'title' => 'Technical feasibility',
                    'body' => implode("\n", [
                        'The classification target is >= 90% accuracy, while current pilot results reach 93-96%.',
                        'Target decision latency is <= 200 ms so the actuator can divert each item at the correct conveyor position.',
                        'The intended capacity is 1,000-2,000 fruits per hour depending on produce type and machine configuration.',
                        'Lot-level QR codes are generated within <= 5 seconds, with data retained for at least 12-24 months.',
                        'The system is designed for safe operation with an electrical cabinet, emergency stop, and status or fault alerts.',
                    ]),
                ],
            ],
            'contact' => [
                'vi' => [
                    'title' => 'Liên hệ dự án',
                    'body' => implode("\n", [
                        'Email dự án: agrisort.ai.team@gmail.com',
                        'Website chỉ công khai đầu mối dự án; số điện thoại và email cá nhân của thành viên hoặc giảng viên hướng dẫn không hiển thị công khai.',
                    ]),
                ],
                'en' => [
                    'title' => 'Project contact',
                    'body' => implode("\n", [
                        'Project email: agrisort.ai.team@gmail.com',
                        'Only the project mailbox is shown publicly; private phone numbers and personal email addresses are not displayed.',
                    ]),
                ],
            ],
        ];
    }

    /**
     * @return string[]
     */
    public static function sectionKeys(): array
    {
        return array_keys(self::cmsSections());
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function localizedSectionDefaults(string $locale): array
    {
        $localized = [];

        foreach (self::cmsSections() as $key => $payload) {
            $localized[$key] = self::pickLocale($payload, $locale);
        }

        return $localized;
    }

    /**
     * @return array<string, mixed>
     */
    public static function homeViewData(string $locale): array
    {
        return [
            'hero_kicker' => self::text([
                'vi' => 'Máy phân loại AI + QR truy xuất nguồn gốc',
                'en' => 'AI sorting machine + QR traceability',
            ], $locale),
            'hero_metrics' => [
                [
                    'value' => '93-96%',
                    'label' => self::text([
                        'vi' => 'Độ chính xác thử nghiệm',
                        'en' => 'Pilot accuracy',
                    ], $locale),
                ],
                [
                    'value' => '1.000-2.000',
                    'label' => self::text([
                        'vi' => 'quả/giờ',
                        'en' => 'fruits/hour',
                    ], $locale),
                ],
                [
                    'value' => '50-70%',
                    'label' => self::text([
                        'vi' => 'giảm lao động phân loại trực tiếp',
                        'en' => 'direct sorting labor reduction',
                    ], $locale),
                ],
            ],
            'hero_cards' => [
                [
                    'label' => self::text([
                        'vi' => 'Camera & AI',
                        'en' => 'Camera & AI',
                    ], $locale),
                    'text' => self::text([
                        'vi' => 'Nhận diện màu sắc, kích thước, độ chín và khuyết tật bề mặt trên từng quả.',
                        'en' => 'Detect color, size, ripeness, and surface defects on each fruit.',
                    ], $locale),
                ],
                [
                    'label' => self::text([
                        'vi' => 'Cơ cấu gạt',
                        'en' => 'Sorting actuator',
                    ], $locale),
                    'text' => self::text([
                        'vi' => 'Đồng bộ quyết định AI với vị trí sản phẩm để chia Loại 1, Loại 2 và Lỗi.',
                        'en' => 'Synchronize AI decisions with conveyor position to separate Grade 1, Grade 2, and defect outputs.',
                    ], $locale),
                ],
                [
                    'label' => self::text([
                        'vi' => 'QR theo mã lô',
                        'en' => 'Lot-level QR',
                    ], $locale),
                    'text' => self::text([
                        'vi' => 'Lưu dữ liệu chất lượng, vùng trồng và thời điểm thu hoạch để truy xuất minh bạch.',
                        'en' => 'Store quality, origin, and harvest data for transparent lot-level traceability.',
                    ], $locale),
                ],
            ],
            'workflow_steps' => [
                self::text([
                    'vi' => '1. Nông sản đi qua băng tải và camera ghi hình',
                    'en' => '1. Produce moves on the conveyor while cameras capture images',
                ], $locale),
                self::text([
                    'vi' => '2. AI phân tích và ra quyết định phân hạng theo thời gian thực',
                    'en' => '2. AI analyzes the images and makes a real-time grading decision',
                ], $locale),
                self::text([
                    'vi' => '3. Dữ liệu lô được lưu và sinh QR để truy xuất sau phân loại',
                    'en' => '3. Lot data is stored and a QR code is generated for post-sorting traceability',
                ], $locale),
            ],
            'capability_order' => ['solution', 'technology', 'impact', 'swot', 'roadmap', 'team'],
            'capability_configs' => [
                'solution' => [
                    'kicker' => self::text([
                        'vi' => 'Đúng bài toán sau thu hoạch',
                        'en' => 'Built for post-harvest needs',
                    ], $locale),
                    'produce' => 'mango',
                ],
                'technology' => [
                    'kicker' => self::text([
                        'vi' => 'Camera -> AI -> Cơ cấu gạt',
                        'en' => 'Camera -> AI -> Actuator',
                    ], $locale),
                    'produce' => 'orange',
                ],
                'impact' => [
                    'kicker' => self::text([
                        'vi' => 'Hiệu quả kinh tế - xã hội',
                        'en' => 'Economic and social gain',
                    ], $locale),
                    'produce' => 'avocado',
                ],
                'swot' => [
                    'kicker' => self::text([
                        'vi' => 'Chuỗi giá trị nông sản',
                        'en' => 'Agricultural value chain',
                    ], $locale),
                    'produce' => 'dragonfruit',
                ],
                'roadmap' => [
                    'kicker' => self::text([
                        'vi' => 'Pilot -> mở rộng -> ASEAN',
                        'en' => 'Pilot -> scale -> ASEAN',
                    ], $locale),
                    'produce' => 'banana',
                ],
                'team' => [
                    'kicker' => self::text([
                        'vi' => 'Liên ngành và thực chiến',
                        'en' => 'Cross-functional execution',
                    ], $locale),
                    'produce' => 'mango',
                ],
            ],
            'feasibility' => [
                'kicker' => self::text([
                    'vi' => 'Tính khả thi kỹ thuật',
                    'en' => 'Technical feasibility',
                ], $locale),
                'summary' => self::text([
                    'vi' => 'Thiết kế được định hướng để đủ mạnh cho giai đoạn nguyên mẫu, thử nghiệm thực tế và mở rộng thành sản phẩm có khả năng thương mại hóa.',
                    'en' => 'The design is scoped to support prototyping, real-world validation, and expansion toward a commercially viable product.',
                ], $locale),
                'badges' => [
                    self::text([
                        'vi' => '>= 90% mục tiêu kỹ thuật',
                        'en' => '>= 90% technical target',
                    ], $locale),
                    self::text([
                        'vi' => '<= 200 ms quyết định',
                        'en' => '<= 200 ms decision latency',
                    ], $locale),
                    self::text([
                        'vi' => 'Lưu dữ liệu 12-24 tháng',
                        'en' => '12-24 month data retention',
                    ], $locale),
                ],
            ],
            'blog' => [
                'kicker' => self::text([
                    'vi' => 'Bài viết dự án',
                    'en' => 'Project posts',
                ], $locale),
                'title' => self::text([
                    'vi' => 'Tin tức và tài liệu công khai về AGRISORT-AI',
                    'en' => 'Public updates and materials about AGRISORT-AI',
                ], $locale),
                'empty' => self::text([
                    'vi' => 'Chưa có bài viết nào được xuất bản.',
                    'en' => 'No posts have been published yet.',
                ], $locale),
            ],
            'consult' => [
                'kicker' => self::text([
                    'vi' => 'Đăng ký tư vấn',
                    'en' => 'Consultation request',
                ], $locale),
                'intro' => self::text([
                    'vi' => 'Đăng ký để nhận khảo sát hiện trạng, tư vấn cấu hình máy và phương án triển khai phù hợp cho HTX hoặc cơ sở sơ chế của bạn.',
                    'en' => 'Register to receive an operational assessment, machine configuration guidance, and a rollout proposal for your cooperative or processing facility.',
                ], $locale),
            ],
            'contact' => [
                'kicker' => self::text([
                    'vi' => 'Liên hệ hợp tác',
                    'en' => 'Partnership contact',
                ], $locale),
                'privacy_note' => self::text([
                    'vi' => 'Thông tin liên hệ được tiếp nhận qua đầu mối dự án; dữ liệu cá nhân của thành viên và giảng viên hướng dẫn không công khai trên website.',
                    'en' => 'Contacts are handled through the project mailbox; private information of team members and advisors is not published on the website.',
                ], $locale),
            ],
        ];
    }

    private static function text(array $translations, string $locale): string
    {
        $vi = (string) ($translations['vi'] ?? '');
        if ($locale !== 'en') {
            return $vi;
        }

        $fallback = (string) ($translations['en'] ?? '');
        $translated = TranslateService::toEnglish($vi);
        if (
            trim($fallback) !== ''
            && $translated === $vi
            && preg_match('/[^\x00-\x7F]/u', $vi) === 1
        ) {
            return $fallback;
        }

        return $translated !== '' ? $translated : ($fallback !== '' ? $fallback : $vi);
    }

    /**
     * @param array{vi?: array<string, string>, en?: array<string, string>} $payload
     * @return array<string, string>
     */
    private static function pickLocale(array $payload, string $locale): array
    {
        $vi = isset($payload['vi']) && is_array($payload['vi']) ? $payload['vi'] : [];
        if ($locale !== 'en') {
            return $vi;
        }

        if ($vi !== []) {
            return TranslateService::structuredToEnglish($vi);
        }

        return isset($payload['en']) && is_array($payload['en']) ? $payload['en'] : [];
    }
}

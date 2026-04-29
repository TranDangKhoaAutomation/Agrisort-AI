package com.example.agrisort_ai.features.dashboard.presentation

import com.example.agrisort_ai.BuildConfig

import com.example.agrisort_ai.features.dashboard.domain.model.AdminDashboardSummary
import com.example.agrisort_ai.features.dashboard.domain.model.AdminUser
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerLot
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerPackage
import com.example.agrisort_ai.features.dashboard.domain.model.SupplyAssignment
import com.example.agrisort_ai.features.dashboard.domain.model.SupplyEvent
import com.example.agrisort_ai.features.dashboard.domain.model.TraceAssignment
import com.example.agrisort_ai.features.dashboard.domain.model.TraceAttachment
import com.example.agrisort_ai.features.dashboard.domain.model.TraceEvent
import com.example.agrisort_ai.features.dashboard.domain.model.TraceLookupResult

data class ShowcaseMetric(
    val title: String,
    val value: String,
    val caption: String
)

data class ShowcasePoint(
    val title: String,
    val description: String
)

object AgriSortShowcaseContent {
    const val projectName = "AGRISORT-AI"
    const val projectTagline = "Máy phân loại nông sản thông minh tích hợp truy xuất QR"
    const val projectSummary = "AGRISORT-AI là giải pháp phân loại nông sản dùng camera, xử lý ảnh và mô hình nhận diện để đánh giá màu sắc, kích thước, độ chín và khuyết tật bề mặt. Kết quả phân loại được lưu theo mã lô, đồng bộ thời gian thực và gắn QR truy xuất ngay trên dây chuyền."

    val homeMetrics = listOf(
        ShowcaseMetric("Độ chính xác mục tiêu", "93-96%", "Hiệu chỉnh theo từng loại trái cây và mùa vụ."),
        ShowcaseMetric("Thời gian ra quyết định", "<= 200 ms", "Đủ nhanh để xử lý ngay trên dây chuyền."),
        ShowcaseMetric("Năng suất dự kiến", "1.000-2.000 quả/giờ", "Phù hợp quy mô HTX, trạm thu mua và cơ sở sơ chế."),
        ShowcaseMetric("Giảm chi phí phân loại", "50-70%", "Giảm phụ thuộc vào thao tác thủ công lặp lại."),
    )

    val painPoints = listOf(
        ShowcasePoint(
            "Phân loại còn phụ thuộc cảm quan",
            "Đánh giá bằng mắt thường dễ lệch giữa các ca làm việc, làm chất lượng lô không đồng đều và khó chuẩn hóa khi mở rộng."
        ),
        ShowcasePoint(
            "Thiếu dữ liệu theo mã lô",
            "Nhiều điểm thu mua và sơ chế chưa lưu dữ liệu tập trung theo lô, khiến việc truy lại nguồn gốc mất thời gian và thiếu nhất quán."
        ),
        ShowcasePoint(
            "Thất thoát sau thu hoạch còn cao",
            "Nếu phát hiện lỗi chậm hoặc không tách nhóm chất lượng sớm, doanh nghiệp sẽ tăng hao hụt và giảm giá trị thương mại."
        )
    )

    val solutionBlocks = listOf(
        ShowcasePoint(
            "Máy phân loại chuyên cho nông sản Việt Nam",
            "Thiết kế xoay quanh camera, xử lý ảnh, nhận diện chất lượng và cơ cấu gạt để phù hợp điều kiện vận hành thực tế."
        ),
        ShowcasePoint(
            "QR truy xuất theo mã lô và mã kiện",
            "Mỗi lô hoặc kiện đều có QR riêng để HTX, cơ sở sơ chế, logistics và người mua tra cứu cùng một nguồn dữ liệu."
        ),
        ShowcasePoint(
            "Ứng dụng theo vai trò vận hành",
            "App hỗ trợ nhập liệu hiện trường, đóng gói QR, ghi nhận sự kiện và theo dõi trạng thái công khai theo đúng vai trò."
        )
    )

    val technologyStack = listOf(
        ShowcasePoint("Camera thu ảnh", "Ghi nhận hình ảnh nông sản trên băng tải theo thời gian thực."),
        ShowcasePoint("Xử lý ảnh", "Chuẩn hóa ảnh đầu vào để tách màu sắc, kích thước và dấu hiệu khuyết tật bề mặt."),
        ShowcasePoint("Mô hình phân loại", "Đưa ra gợi ý phân hạng và đánh dấu trường hợp cần tách lỗi."),
        ShowcasePoint("Quyết định cơ cấu gạt", "Kết quả được chuyển thành thao tác phân nhóm trực tiếp trên dây chuyền."),
        ShowcasePoint("QR và lưu dữ liệu lô", "Thông tin lô, kiện và sự kiện được đồng bộ để truy xuất trong 12-24 tháng."),
    )

    val roadmap = listOf(
        ShowcasePoint("Giai đoạn 1", "Pilot tại điểm thu mua hoặc cơ sở sơ chế để chuẩn hóa dữ liệu lô, QR và các mốc truy xuất."),
        ShowcasePoint("Giai đoạn 2", "Hiệu chỉnh mô hình theo từng mùa vụ, từng loại trái cây và điều kiện ánh sáng thực tế."),
        ShowcasePoint("Giai đoạn 3", "Mở rộng sang nhiều HTX, trạm thu mua và nhóm đóng gói có nhu cầu giảm thất thoát sau thu hoạch."),
        ShowcasePoint("Giai đoạn 4", "Kết nối doanh nghiệp nhỏ-vừa, logistics và kênh bán lẻ cần minh bạch nguồn gốc."),
    )

    val targetSegments = listOf(
        ShowcasePoint("Hợp tác xã và nông hộ", "Cần chuẩn hóa lô hàng và nâng độ tin cậy khi bán ra thị trường."),
        ShowcasePoint("Trạm thu mua và cơ sở sơ chế", "Cần giảm thời gian phân loại và ghi nhận dữ liệu lô ngay tại hiện trường."),
        ShowcasePoint("Doanh nghiệp nông sản nhỏ-vừa", "Cần truy xuất minh bạch và kiểm soát chất lượng theo kiện hàng."),
        ShowcasePoint("Người mua và đơn vị phân phối", "Cần quét QR để kiểm tra nguồn gốc, trạng thái công khai và hành trình lô hàng."),
    )

    val adminSummary = AdminDashboardSummary(
        totalUsers = 128,
        activeUsers = 94,
        totalLots = 238,
        publishedLots = 176,
        pendingPartners = 14
    )

    val adminUsers = listOf(
        AdminUser(12, "HTX Xoài Cao Lãnh", "caolanh@agrisort.vn", "partner", "active"),
        AdminUser(27, "Kho Bình Dương", "warehouse.bd@agrisort.vn", "warehouse", "active"),
        AdminUser(31, "Nhà vận chuyển Mekong", "transport.mekong@agrisort.vn", "transporter", "inactive"),
        AdminUser(44, "Chuỗi bán lẻ xanh", "retail.green@agrisort.vn", "seller", "active"),
        AdminUser(57, "Tổ hợp tác thử nghiệm", "pilot.team@agrisort.vn", "partner", "pending"),
    )

    val traceAssignments = listOf(
        TraceAssignment(1, "lot", 101, "harvest-checkin", 12, "HTX Xoài Cao Lãnh"),
        TraceAssignment(2, "lot", 101, "warehouse-checkin", 27, "Kho Bình Dương"),
        TraceAssignment(3, "package", 305, "transport-checkin", 31, "Nhà vận chuyển Mekong"),
        TraceAssignment(4, "package", 305, "retail-display", 44, "Chuỗi bán lẻ xanh"),
    )

    val traceEvents = listOf(
        TraceEvent(
            id = 1,
            stageCode = "harvest-checkin",
            eventTime = "2026-03-04 06:30:00",
            locationName = "Cao Lãnh, Đồng Tháp",
            note = "Thu hoạch lô xoài đạt độ chín tiêu chuẩn.",
            actorName = "Tổ kỹ thuật HTX"
        ),
        TraceEvent(
            id = 2,
            stageCode = "ai-sorting",
            eventTime = "2026-03-04 09:15:00",
            locationName = "Trạm phân loại AGRISORT",
            note = "Hệ thống phân 3 nhóm chất lượng, mục tiêu sai lệch thủ công dưới 5%.",
            actorName = "Máy phân loại"
        ),
        TraceEvent(
            id = 3,
            stageCode = "warehouse-checkin",
            eventTime = "2026-03-04 13:20:00",
            locationName = "Kho Bình Dương",
            note = "Kiểm nhận nhiệt độ và niêm phong kiện hàng.",
            actorName = "Đội kho",
            attachments = listOf(
                TraceAttachment(
                    1,
                    "bien-ban-kho.pdf",
                    "${BuildConfig.PUBLIC_BASE_URL.trimEnd('/')}/storage/demo/bien-ban-kho.pdf"
                )
            )
        ),
        TraceEvent(
            id = 4,
            stageCode = "retail-display",
            eventTime = "2026-03-05 08:40:00",
            locationName = "Điểm bán Thủ Đức",
            note = "Đưa lô đạt chuẩn lên quầy với nhãn QR truy xuất đầy đủ.",
            actorName = "Quản lý điểm bán"
        )
    )

    val partnerLots = listOf(
        PartnerLot(
            id = 101,
            lotCode = "LO-20260304-BG01",
            produceType = "Vải thiều",
            originRegion = "Bắc Giang",
            harvestDate = "2026-03-04",
            grade1Count = 240,
            grade2Count = 64,
            defectCount = 12,
            notes = "Lô đầu ngày, độ đồng đều ổn định.",
            publishStatus = "published",
            qrToken = "agrisort-lot-001"
        ),
        PartnerLot(
            id = 102,
            lotCode = "LO-20260304-HY02",
            produceType = "Nhãn",
            originRegion = "Hưng Yên",
            harvestDate = "2026-03-04",
            grade1Count = 180,
            grade2Count = 50,
            defectCount = 18,
            notes = "Cần theo dõi lại tỷ lệ lỗi ở cuối ca.",
            publishStatus = "draft",
            qrToken = "agrisort-lot-002"
        ),
        PartnerLot(
            id = 103,
            lotCode = "LO-20260305-SL03",
            produceType = "Xoài",
            originRegion = "Sơn La",
            harvestDate = "2026-03-05",
            grade1Count = 210,
            grade2Count = 72,
            defectCount = 9,
            notes = "Lô đã sẵn sàng cho đóng gói.",
            publishStatus = "published",
            qrToken = "agrisort-lot-003"
        ),
    )

    val partnerPackages = listOf(
        PartnerPackage(
            id = 305,
            lotId = 101,
            packageCode = "PK-XL-001",
            packageLabel = "Thùng xoài 10kg",
            quantity = 12,
            netWeightKg = "10.000",
            publishStatus = "published",
            qrToken = "agrisort-package-001"
        ),
        PartnerPackage(
            id = 306,
            lotId = 101,
            packageCode = "PK-XL-002",
            packageLabel = "Thùng xoài 8kg",
            quantity = 10,
            netWeightKg = "8.000",
            publishStatus = "published",
            qrToken = "agrisort-package-002"
        ),
        PartnerPackage(
            id = 307,
            lotId = 103,
            packageCode = "PK-MN-001",
            packageLabel = "Khay măng cụt",
            quantity = 16,
            netWeightKg = "6.500",
            publishStatus = "draft",
            qrToken = "agrisort-package-003"
        ),
    )

    val supplyAssignments = listOf(
        SupplyAssignment(1, "lot", 101, "warehouse-checkin", "Nhập kho"),
        SupplyAssignment(2, "package", 305, "transport-checkin", "Lên xe"),
        SupplyAssignment(3, "package", 305, "retail-display", "Trưng bày"),
    )

    val supplyEvents = listOf(
        SupplyEvent(1, "lot", 101, "warehouse-checkin", "2026-03-04 13:20:00", "Kho Bình Dương", "Kiểm tra nhiệt độ và độ kín thùng"),
        SupplyEvent(2, "package", 305, "transport-checkin", "2026-03-04 16:00:00", "Bến xe lạnh", "Xác nhận xe lạnh sẵn sàng vận chuyển"),
        SupplyEvent(3, "package", 305, "retail-display", "2026-03-05 08:40:00", "Điểm bán Thủ Đức", "Kiểm tra QR hiển thị đúng trước khi lên quầy"),
    )

    val adminFocus = listOf(
        ShowcasePoint("Chuẩn hóa dữ liệu đầu vào", "Đảm bảo lô mới có đủ vùng trồng, ngày thu hoạch, phân hạng và QR trước khi công khai."),
        ShowcasePoint("Duyệt đúng tài khoản vận hành", "Giảm rủi ro cấp sai vai trò cho điểm thu mua, đóng gói hoặc logistics."),
        ShowcasePoint("Theo dõi tỷ lệ công khai", "Chỉ phát hành QR cho các lô có dữ liệu đủ tin cậy và có thể đối soát."),
    )

    val partnerFocus = listOf(
        ShowcasePoint("Chuẩn hóa chất lượng lô", "Thu thập dữ liệu đồng đều để cải thiện độ chính xác ở những mùa vụ sau."),
        ShowcasePoint("Đóng gói gắn với QR", "Mỗi kiện nên có mã riêng để phản ánh đúng số lượng, khối lượng và lịch sử bàn giao."),
        ShowcasePoint("Giảm thất thoát sau thu hoạch", "Theo dõi defect count và ghi chú bất thường để điều chỉnh quy trình sơ chế."),
    )

    val supplyFocus = listOf(
        ShowcasePoint("Xác nhận stage tại hiện trường", "Mỗi lần bàn giao đều nên có thời gian, vị trí và ghi chú minh bạch."),
        ShowcasePoint("Bám theo token thay vì giấy rời", "Token giúp truy ngược nhanh khi có khiếu nại hoặc lệch thông tin."),
        ShowcasePoint("Lưu bằng chứng vận hành", "Ảnh, biên bản và ghi chú nên đi cùng sự kiện để tăng độ tin cậy khi đối soát."),
    )

    fun demoTraceResult(token: String): TraceLookupResult {
        return TraceLookupResult(
            token = token,
            entityType = "lot",
            lotCode = "LO-DEMO-AGRISORT",
            packageCode = "PK-DEMO-AGRISORT",
            produceType = "Xoài cát chu",
            publishStatus = "published",
            events = traceEvents,
            attachments = traceEvents.flatMap { it.attachments }.ifEmpty {
                listOf(
                    TraceAttachment(
                        id = 99,
                        fileName = "gioi-thieu-agrisort-ai.pdf",
                        fileUrl = "${BuildConfig.PUBLIC_BASE_URL.trimEnd('/')}/storage/demo/gioi-thieu-agrisort-ai.pdf"
                    )
                )
            }
        )
    }
}

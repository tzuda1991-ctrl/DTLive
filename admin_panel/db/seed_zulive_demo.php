<?php
/**
 * Idempotent demo data seeder for ZuLive (INR ₹ currency & full end-to-end flows).
 * Runs automatically on container startup via docker-entrypoint.sh when content tables are empty.
 */

if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tableCount = function (string $table) use ($pdo): int {
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    } catch (Throwable $e) {
        return -1;
    }
};

// Always ensure currency is INR (₹) if still set to default USD/$
$pdo->exec("UPDATE tbl_general_setting SET value = 'INR', updated_at = NOW() WHERE `key` = 'currency' AND value IN ('USD', '')");
$pdo->exec("UPDATE tbl_general_setting SET value = '₹', updated_at = NOW() WHERE `key` = 'currency_code' AND value IN ('$', '')");

$firstSeed = ($tableCount('tbl_package') === 0 && $tableCount('tbl_video') === 0);
if ($firstSeed) {
    $pdo->exec("UPDATE tbl_general_setting SET value = '1', updated_at = NOW() WHERE `key` IN ('refer_and_earn_status', 'multiple_device_sync', 'on_boarding_screen_status', 'subscription_status', 'rent_status', 'coupon_status', 'auto_approve_reviews', 'parent_control_status', 'watchlist_status', 'download_status', 'continue_watching_status', 'active_tv_status')");
    $pdo->exec("UPDATE tbl_general_setting SET value = '50', updated_at = NOW() WHERE `key` = 'parent_user_earn' AND value IN ('0', '')");
    $pdo->exec("UPDATE tbl_general_setting SET value = '25', updated_at = NOW() WHERE `key` = 'child_user_earn' AND value IN ('0', '')");
    $pdo->exec("UPDATE tbl_general_setting SET value = '4', updated_at = NOW() WHERE `key` = 'no_of_device_sync' AND value IN ('0', '')");
    $pdo->exec("UPDATE tbl_payment_option SET visibility = '1', updated_at = NOW() WHERE name = 'cash'");
}

try {
    $pdo->beginTransaction();

    // 1. Types (Top Tabs: Movies=1, TV Shows=2, Live TV=6, Kids=7, Shorts=8)
    if ($tableCount('tbl_type') <= 1) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO tbl_type (id, name, type, storage_type, icon, sort_order, status) VALUES (?, ?, ?, 1, '', ?, 1)");
        $types = [
            [1, 'Movies',   1, 1],
            [2, 'TV Shows', 2, 2],
            [3, 'Live TV',  6, 3],
            [4, 'Kids',     7, 4],
            [5, 'Shorts',   8, 5],
        ];
        foreach ($types as $t) {
            $stmt->execute($t);
        }
    }

    // 2. Categories
    if ($tableCount('tbl_category') === 0) {
        $stmt = $pdo->prepare("INSERT INTO tbl_category (id, name, storage_type, image, sort_order, status) VALUES (?, ?, 1, ?, ?, 1)");
        $cats = [
            [1, 'Action',      'zrcat_action.jpg',   1],
            [2, 'Thriller',    'zrcat_thriller.jpg', 2],
            [3, 'Comedy',      'zrcat_comedy.jpg',   3],
            [4, 'Drama',       'zrcat_drama.jpg',    4],
            [5, 'Sci-Fi',      'zrcat_scifi.jpg',    5],
            [6, 'Romance',     'zrcat_romance.jpg',  6],
            [7, 'Documentary', 'zrcat_doc.jpg',      7],
            [8, 'Kids',        'zrcat_kids.jpg',     8],
        ];
        foreach ($cats as $c) {
            $stmt->execute($c);
        }
    }

    // 3. Languages
    if ($tableCount('tbl_language') === 0) {
        $stmt = $pdo->prepare("INSERT INTO tbl_language (id, name, storage_type, image, sort_order, status) VALUES (?, ?, 1, ?, ?, 1)");
        $langs = [
            [1, 'Hindi',    'zrlang_hi.jpg', 1],
            [2, 'English',  'zrlang_en.jpg', 2],
            [3, 'Tamil',    'zrlang_ta.jpg', 3],
            [4, 'Telugu',   'zrlang_te.jpg', 4],
            [5, 'Gujarati', 'zrlang_gu.jpg', 5],
        ];
        foreach ($langs as $l) {
            $stmt->execute($l);
        }
    }

    // 4. Cast & Crew
    if ($tableCount('tbl_cast') === 0) {
        $stmt = $pdo->prepare("INSERT INTO tbl_cast (id, name, storage_type, image, type, personal_info, status) VALUES (?, ?, 1, ?, ?, ?, 1)");
        $casts = [
            [1, 'Aarav Kapoor',    'zrcast1.jpg', 'Actor',    'Versatile lead actor known for high-octane action thrillers and ZuLive Originals.'],
            [2, 'Meera Nair',      'zrcast2.jpg', 'Actress',  'Critically acclaimed actress celebrated across Hindi, Tamil, and Malayalam cinema.'],
            [3, 'Vikram Rathore',  'zrcast3.jpg', 'Director', 'Filmmaker specializing in neo-noir sci-fi dramas and gripping mystery series.'],
            [4, 'Priya Sharma',    'zrcast4.jpg', 'Actress',  'Breakout OTT star featured in romantic dramas and crime anthologies.'],
            [5, 'Kabir Malhotra',  'zrcast5.jpg', 'Actor',    'Character actor bringing depth to espionage thrillers and comedy series.'],
            [6, 'Ananya Deshmukh', 'zrcast6.jpg', 'Director', 'Showrunner and creator behind ZuLive flagship original productions.'],
        ];
        foreach ($casts as $c) {
            $stmt->execute($c);
        }
    }

    // 5. Channels
    if ($tableCount('tbl_channel') === 0) {
        $stmt = $pdo->prepare("INSERT INTO tbl_channel (id, name, storage_type, portrait_img, landscape_img, is_title, status) VALUES (?, ?, 1, ?, ?, 1, 1)");
        $channels = [
            [1, 'ZuLive Cinema HD',  'zrch1_p.jpg', 'zrch1_l.jpg'],
            [2, 'ZuLive Action+',    'zrch2_p.jpg', 'zrch2_l.jpg'],
            [3, 'ZuLive Kids Club',  'zrch3_p.jpg', 'zrch3_l.jpg'],
            [4, 'ZuLive News 24x7',  'zrch4_p.jpg', 'zrch4_l.jpg'],
        ];
        foreach ($channels as $ch) {
            $stmt->execute($ch);
        }
    }

    // 6. Profile Avatars
    if ($tableCount('tbl_avatar') === 0) {
        $stmt = $pdo->prepare("INSERT INTO tbl_avatar (id, name, storage_type, image, sort_order, status) VALUES (?, ?, 1, ?, ?, 1)");
        for ($i = 1; $i <= 6; $i++) {
            $stmt->execute([$i, "Avatar $i", "zrav{$i}.jpg", $i]);
        }
    }

    // 7. Onboarding Screens
    if ($tableCount('tbl_onboarding_screen') === 0) {
        $stmt = $pdo->prepare("INSERT INTO tbl_onboarding_screen (id, title, storage_type, image, description, status) VALUES (?, ?, 1, ?, ?, 1)");
        $screens = [
            [1, 'Stream Blockbusters & Live TV', 'zronboard1.jpg', 'Watch HD movies, original web series, and 24x7 live channels anytime on ZuLive.'],
            [2, 'Bite-Sized ZuLive Shorts',     'zronboard2.jpg', 'Discover fast vertical micro-dramas, comedy clips, and curated reels in seconds.'],
            [3, 'Flexible Plans in ₹ (INR)',    'zronboard3.jpg', 'Start free or upgrade with affordable INR plans, promo coupons, and instant rentals.'],
        ];
        foreach ($screens as $s) {
            $stmt->execute($s);
        }
    }

    // 8. Social Links
    if ($tableCount('tbl_social_link') === 0) {
        $stmt = $pdo->prepare("INSERT INTO tbl_social_link (id, name, storage_type, image, url, status) VALUES (?, ?, 1, ?, ?, 1)");
        $links = [
            [1, 'Instagram', 'social_ig.jpg', 'https://www.instagram.com/'],
            [2, 'YouTube',   'social_yt.jpg', 'https://www.youtube.com/'],
            [3, 'X',         'social_x.jpg',  'https://x.com/'],
        ];
        foreach ($links as $lk) {
            $stmt->execute($lk);
        }
    }

    // 9. Demo Producer
    if ($tableCount('tbl_producer') === 0) {
        $pass = password_hash('producer123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO tbl_producer (id, user_name, full_name, email, password, mobile_number, storage_type, image, wallet, status) VALUES (1, 'zuliveoriginals', 'ZuLive Originals', 'producer@zulive.app', ?, '9876543210', 1, '', 0, 1)");
        $stmt->execute([$pass]);
    }

    // 10. Subscription Packages & Details (in INR ₹)
    if ($tableCount('tbl_package') === 0) {
        $stmt = $pdo->prepare("INSERT INTO tbl_package (id, package_type, name, price, type, time, watch_on_laptop_tv, ads_free_content, no_of_device_sync, android_product_package, ios_product_package, web_product_package, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $pkgs = [
            [1, 2, 'Free Trial',        0.00,   'Days',  '7', 0, 0, 1, 'zulive_free_trial',    'zulive_free_trial',    'zulive_free_trial'],
            [2, 1, 'Mobile Monthly',    149.00, 'Month', '1', 0, 1, 1, 'zulive_mobile_149',    'zulive_mobile_149',    'zulive_mobile_149'],
            [3, 1, 'Super Value',       299.00, 'Month', '1', 1, 1, 2, 'zulive_super_299',     'zulive_super_299',     'zulive_super_299'],
            [4, 1, 'ZuLive VIP Annual', 999.00, 'Year',  '1', 1, 1, 4, 'zulive_vip_999',       'zulive_vip_999',       'zulive_vip_999'],
        ];
        foreach ($pkgs as $p) {
            $stmt->execute($p);
        }

        $dStmt = $pdo->prepare("INSERT INTO tbl_package_detail (package_id, package_key, package_value) VALUES (?, ?, ?)");
        $details = [
            [1, 'Watch on 1 Device',        '1'],
            [1, 'Use only on Mobile',       '1'],
            [1, 'Ads-Free Streaming',       '0'],
            [1, 'Video Quality',            '720p'],

            [2, 'Watch on 1 Device',        '1'],
            [2, 'Watch on Mobile Only',     '1'],
            [2, 'Ads-Free Streaming',       '1'],
            [2, 'Offline Downloads',        '1'],
            [2, 'Video Quality',            '1080p'],

            [3, 'Watch on 2 Devices',       '2'],
            [3, 'Watch on Mobile & TV',     '1'],
            [3, 'Ads-Free Streaming',       '1'],
            [3, 'Offline Downloads',        '1'],
            [3, 'Video Quality',            '1080p'],

            [4, 'Watch on 4 Devices',       '4'],
            [4, 'Watch on Mobile & TV',     '1'],
            [4, 'Ads-Free Streaming',       '1'],
            [4, 'Offline Downloads',        '1'],
            [4, 'Video Quality',            '4K'],
        ];
        foreach ($details as $d) {
            $dStmt->execute($d);
        }
    }

    // 11. Rent Price List (in INR ₹)
    if ($tableCount('tbl_rent_price_list') === 0) {
        $stmt = $pdo->prepare("INSERT INTO tbl_rent_price_list (id, price, android_product_package, ios_product_package, web_price_id, status) VALUES (?, ?, ?, ?, ?, 1)");
        $rents = [
            [1, 49.00,  'zulive_rent_49',  'zulive_rent_49',  'zulive_rent_49'],
            [2, 99.00,  'zulive_rent_99',  'zulive_rent_99',  'zulive_rent_99'],
            [3, 149.00, 'zulive_rent_149', 'zulive_rent_149', 'zulive_rent_149'],
            [4, 199.00, 'zulive_rent_199', 'zulive_rent_199', 'zulive_rent_199'],
        ];
        foreach ($rents as $r) {
            $stmt->execute($r);
        }
    }

    // 12. Coupons (INR & Percentage)
    if ($tableCount('tbl_coupon') === 0) {
        $stmt = $pdo->prepare("INSERT INTO tbl_coupon (id, code, title, description, start_date, end_date, discount_type, discount_value, applicable_for, package_id, usage_limit, usage_per_user, used_count, is_single_use, status) VALUES (?, ?, ?, ?, '2026-01-01', '2028-12-31', ?, ?, ?, 0, 1000, 5, 0, 0, 1)");
        $coupons = [
            [1, 'ZULIVE50',   '50% Off Everything', 'Get 50% instant discount on any ZuLive plan or movie rental.', 2, 50.00, 0],
            [2, 'WELCOME100', 'Flat ₹100 Off',      'Save flat ₹100 on Mobile Monthly, Super Value, or VIP Annual plan.', 1, 100.00, 1],
            [3, 'RENT20',     'Flat ₹20 Off Rent',  'Enjoy ₹20 off on your next movie or series rental.', 1, 20.00, 2],
        ];
        foreach ($coupons as $cp) {
            $stmt->execute($cp);
        }
    }

    // 13. Seasons
    if ($tableCount('tbl_season') === 0) {
        $stmt = $pdo->prepare("INSERT INTO tbl_season (id, name, sort_order, status) VALUES (?, ?, ?, 1)");
        $stmt->execute([1, 'Season 1', 1]);
        $stmt->execute([2, 'Season 2', 2]);
        $stmt->execute([3, 'Season 3', 3]);
    }

    // 14. Videos (Movies=1, Upcoming=5, Live Channel=6, Kids=7)
    // All stream URLs verified HTTP 206 reachable!
    if ($tableCount('tbl_video') === 0) {
        $vSintel   = 'https://download.blender.org/durian/trailer/sintel_trailer-720p.mp4';
        $vSintelTr = 'https://media.w3.org/2010/05/sintel/trailer.mp4';
        $vBunny    = 'https://media.w3.org/2010/05/bunny/movie.mp4';
        $vBunnyTr  = 'https://media.w3.org/2010/05/bunny/trailer.mp4';
        $vMovie300 = 'https://media.w3.org/2010/05/video/movie_300.mp4';
        $vBee      = 'https://flutter.github.io/assets-for-api-docs/assets/videos/bee.mp4';
        $vFly      = 'https://flutter.github.io/assets-for-api-docs/assets/videos/butterfly.mp4';
        $vHlsMux   = 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8';
        $vHlsTears = 'https://demo.unified-streaming.com/k8s/features/stable/video/tears-of-steel/tears-of-steel.ism/.m3u8';
        $vHlsApple = 'https://devstreaming-cdn.apple.com/videos/streaming/examples/img_bipbop_adv_example_fmp4/master.m3u8';

        $vStmt = $pdo->prepare("
            INSERT INTO tbl_video (
                id, type_id, video_type, channel_id, producer_id, category_id, language_id, cast_id,
                name, storage_type, thumbnail, landscape, description,
                video_storage_type, video_upload_type, video_320, video_480, video_720, video_1080,
                video_extension, video_duration,
                trailer_storage_type, trailer_type, trailer_url,
                subtitle_storage_type, subtitle_type, subtitle_lang_1, subtitle_1, subtitle_lang_2, subtitle_2, subtitle_lang_3, subtitle_3,
                release_date, is_premium, is_title, is_download, is_comment, is_like,
                is_rent, price, rent_day, avg_rating, total_review, total_view, total_like, status
            ) VALUES (
                ?, ?, ?, ?, 1, ?, ?, ?,
                ?, 1, ?, ?, ?,
                1, ?, ?, ?, ?, ?,
                ?, ?,
                1, 'external', ?,
                1, 'external', '', '', '', '', '', '',
                ?, ?, 1, ?, 1, 1,
                ?, ?, ?, ?, ?, ?, ?, 1
            )
        ");

        $videos = [
            // Movies (type_id=1, video_type=1)
            [1, 1, 1, 1, '1,5',   '1,2',   '1,2,3', 'Sintel: The Dragon Quest', 'zr_m1_p.jpg', 'zr_m1_l.jpg', 'A lone warrior travels across treacherous lands in search of a lost baby dragon. Watch free in HD on ZuLive.', 'external', $vSintel, $vSintel, $vSintel, $vSintel, 'mp4', 88000, $vSintelTr, '2026-01-15', 0, 1, 0, 0, 0, 4.8, 12, 18450, 1420],
            [2, 1, 1, 1, '1,2,5', '1,2,3', '1,3,5', 'Tears of Steel',           'zr_m2_p.jpg', 'zr_m2_l.jpg', 'In a futuristic Amsterdam, elite warriors unite to save humanity from rogue cybernetics. ZuLive VIP Exclusive.', 'external', $vHlsTears, $vHlsTears, $vHlsTears, $vHlsTears, 'm3u8', 734000, $vSintelTr, '2026-02-20', 1, 1, 0, 0, 0, 4.9, 18, 24100, 2150],
            [3, 1, 1, 2, '2,4',   '1,2',   '1,4,5', 'Midnight Protocol',        'zr_m3_p.jpg', 'zr_m3_l.jpg', 'An intelligence analyst races against the clock after uncovering a classified blackout plot. Available on Rent ₹99.', 'external', $vHlsMux, $vHlsMux, $vHlsMux, $vHlsMux, 'm3u8', 596000, $vBunnyTr, '2026-03-10', 0, 1, 1, 2, 7, 4.7, 9, 15200, 1180],
            [4, 1, 1, 2, '2,7',   '1,2,4', '2,5,6', 'Shadow Signal',            'zr_m4_p.jpg', 'zr_m4_l.jpg', 'Strange deep-space radio transmissions spark an unforgettable mystery thriller. Stream free or download offline.', 'external', $vMovie300, $vMovie300, $vMovie300, $vMovie300, 'mp4', 120000, $vSintelTr, '2026-03-28', 0, 1, 0, 0, 0, 4.5, 7, 9840, 760],
            [5, 1, 1, 1, '1,5',   '1,2,3', '1,2,6', 'Cyber Horizon',            'zr_m5_p.jpg', 'zr_m5_l.jpg', 'A renegade pilot enters the orbital ring to rescue lost colonists. Rent for ₹149 with 14-day access.', 'external', $vHlsTears, $vHlsTears, $vHlsTears, $vHlsTears, 'm3u8', 720000, $vSintelTr, '2026-04-05', 0, 1, 1, 3, 14, 4.6, 11, 12900, 990],
            [6, 1, 1, 1, '4,6',   '1,5',   '2,4',   'Monsoon Symphony',         'zr_m6_p.jpg', 'zr_m6_l.jpg', 'Two musicians cross paths in Mumbai during the monsoon season in this heartwarming musical drama.', 'external', $vBunny, $vBunny, $vBunny, $vBunny, 'mp4', 596000, $vBunnyTr, '2026-04-18', 0, 1, 0, 0, 0, 4.7, 14, 16300, 1540],

            // Upcoming Trailers (type_id=1, video_type=5)
            [7, 1, 5, 1, '1,5', '1,2', '1,3', 'Solaris Rising', 'zr_u1_p.jpg', 'zr_u1_l.jpg', 'Upcoming sci-fi spectacle arriving soon exclusively on ZuLive. Watch the official teaser now.', 'external', $vSintelTr, $vSintelTr, $vSintelTr, $vSintelTr, 'mp4', 52000, $vSintelTr, '2026-12-20', 0, 0, 0, 0, 0, 4.9, 5, 8400, 910],
            [8, 1, 5, 2, '1,2', '1,4', '2,5', 'Neon Express',   'zr_u2_p.jpg', 'zr_u2_l.jpg', 'An unstoppable midnight train hurtles across India in this upcoming heist thriller.', 'external', $vBunnyTr, $vBunnyTr, $vBunnyTr, $vBunnyTr, 'mp4', 33000, $vBunnyTr, '2026-12-28', 0, 0, 0, 0, 0, 4.8, 4, 6200, 640],

            // Live TV Streams (type_id=3, video_type=6)
            [9,  3, 6, 1, '1,4', '1,2', '1,2', 'ZuLive Cinema 24x7 Live', 'zr_l1_p.jpg', 'zr_l1_l.jpg', 'Non-stop HD cinema showcase streaming 24 hours a day on ZuLive Cinema.', 'live_stream_url', $vHlsMux, $vHlsMux, $vHlsMux, $vHlsMux, 'm3u8', 0, $vSintelTr, '2026-05-01', 0, 0, 0, 0, 0, 4.8, 6, 31200, 2900],
            [10, 3, 6, 2, '1,2', '1,2', '1,5', 'Action Pulse Live TV',    'zr_l2_p.jpg', 'zr_l2_l.jpg', 'Live action blockbusters, stunts, and behind-the-scenes specials all day.', 'live_stream_url', $vHlsTears, $vHlsTears, $vHlsTears, $vHlsTears, 'm3u8', 0, $vSintelTr, '2026-05-01', 0, 0, 0, 0, 0, 4.6, 4, 19800, 1750],
            [11, 3, 6, 3, '3,8', '1,2', '4,6', 'Kids Toon Live Stream',   'zr_l3_p.jpg', 'zr_l3_l.jpg', 'Safe, cheerful, kid-friendly cartoons and educational shows live 24x7.', 'live_stream_url', $vHlsApple, $vHlsApple, $vHlsApple, $vHlsApple, 'm3u8', 0, $vBunnyTr, '2026-05-01', 0, 0, 0, 0, 0, 4.9, 8, 22400, 2100],

            // Kids Movies (type_id=4, video_type=7)
            [12, 4, 7, 3, '3,8', '1,2,5', '4,6', 'Big Buck Bunny',      'zr_k1_p.jpg', 'zr_k1_l.jpg', 'A giant, lovable rabbit teaches three mischievous forest critters a playful lesson.', 'external', $vBunny, $vBunny, $vBunny, $vBunny, 'mp4', 596000, $vBunnyTr, '2026-01-10', 0, 1, 0, 0, 0, 4.9, 20, 41500, 3800],
            [13, 4, 7, 3, '7,8', '1,2',   '4',   'Honeybee Adventure',  'zr_k2_p.jpg', 'zr_k2_l.jpg', 'Follow a curious little honeybee exploring blooming summer meadows.', 'external', $vBee, $vBee, $vBee, $vBee, 'mp4', 15000, $vBee, '2026-02-14', 0, 1, 0, 0, 0, 4.7, 9, 14300, 1290],
            [14, 4, 7, 3, '7,8', '1,2',   '6',   'Butterfly Garden',    'zr_k3_p.jpg', 'zr_k3_l.jpg', 'Colorful butterflies dance through an enchanted forest in this soothing kids classic.', 'external', $vFly, $vFly, $vFly, $vFly, 'mp4', 18000, $vFly, '2026-03-02', 0, 1, 0, 0, 0, 4.8, 11, 16900, 1620],
        ];
        foreach ($videos as $v) {
            $vStmt->execute($v);
        }
    }

    // 15. TV Shows & Episodes
    if ($tableCount('tbl_tv_show') === 0) {
        $vSintel   = 'https://download.blender.org/durian/trailer/sintel_trailer-720p.mp4';
        $vSintelTr = 'https://media.w3.org/2010/05/sintel/trailer.mp4';
        $vBunny    = 'https://media.w3.org/2010/05/bunny/movie.mp4';
        $vBunnyTr  = 'https://media.w3.org/2010/05/bunny/trailer.mp4';
        $vMovie300 = 'https://media.w3.org/2010/05/video/movie_300.mp4';
        $vBee      = 'https://flutter.github.io/assets-for-api-docs/assets/videos/bee.mp4';
        $vFly      = 'https://flutter.github.io/assets-for-api-docs/assets/videos/butterfly.mp4';
        $vHlsMux   = 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8';
        $vHlsTears = 'https://demo.unified-streaming.com/k8s/features/stable/video/tears-of-steel/tears-of-steel.ism/.m3u8';

        $sStmt = $pdo->prepare("
            INSERT INTO tbl_tv_show (
                id, type_id, video_type, channel_id, producer_id, category_id, language_id, cast_id,
                name, storage_type, thumbnail, landscape, trailer_storage_type, trailer_type, trailer_url,
                description, release_date, is_title, is_comment, is_like, is_rent, price, rent_day,
                avg_rating, total_review, total_view, total_like, status
            ) VALUES (
                ?, ?, ?, ?, 1, ?, ?, ?,
                ?, 1, ?, ?, 1, 'external', ?,
                ?, ?, 1, 1, 1, ?, ?, ?,
                ?, ?, ?, ?, 1
            )
        ");

        $shows = [
            [1, 2, 2, 1, '1,2,4', '1,2', '1,2,3', 'Mumbai Chronicles', 'zr_s1_p.jpg', 'zr_s1_l.jpg', $vSintelTr, 'An investigative journalist and a rookie cop untangle a city-wide conspiracy across two gripping seasons.', '2026-02-01', 0, 0, 0, 4.8, 16, 29400, 2600],
            [2, 2, 2, 2, '2,5',   '1,2', '1,4,5', 'Code Zero',         'zr_s2_p.jpg', 'zr_s2_l.jpg', $vBunnyTr,  'When India’s quantum grid is hijacked, an underground cyber squad fights back in real time.', '2026-03-12', 0, 0, 0, 4.7, 11, 21300, 1890],
            [3, 2, 2, 1, '1,2,4', '1,3', '1,2,6', 'Royal Heist',       'zr_s3_p.jpg', 'zr_s3_l.jpg', $vSintelTr, 'A master strategist assembles a crew for an audacious museum heist. Rent full series for ₹199.', '2026-04-08', 1, 4, 14, 4.9, 13, 18700, 1640],
            [4, 4, 7, 3, '3,7,8', '1,2', '4,6',   'Tiny Explorers',    'zr_s4_p.jpg', 'zr_s4_l.jpg', $vBee,      'Fun bite-sized science and nature adventures crafted for curious young minds.', '2026-03-20', 0, 0, 0, 4.8, 10, 25100, 2240],
        ];
        foreach ($shows as $sh) {
            $sStmt->execute($sh);
        }

        $epStmt = $pdo->prepare("
            INSERT INTO tbl_tv_show_video (
                id, show_id, season_id, name, storage_type, thumbnail, landscape, description,
                video_storage_type, video_upload_type, video_320, video_480, video_720, video_1080,
                video_extension, video_duration,
                subtitle_storage_type, subtitle_type, subtitle_lang_1, subtitle_1, subtitle_lang_2, subtitle_2, subtitle_lang_3, subtitle_3,
                is_premium, is_title, is_download, total_view, sort_order, status
            ) VALUES (
                ?, ?, ?, ?, 1, ?, ?, ?,
                1, 'external', ?, ?, ?, ?,
                ?, ?,
                1, 'external', '', '', '', '', '', '',
                ?, 1, 1, ?, ?, 1
            )
        ");

        $episodes = [
            // Show 1: Mumbai Chronicles (Season 1 Free ep1, Premium ep2; Season 2 ep3)
            [1, 1, 1, 'Ep 1: Blackout at Marine Drive', 'zr_s1_p.jpg', 'zr_s1_l.jpg', 'A sudden citywide blackout triggers a high-stakes investigation.', $vSintel, $vSintel, $vSintel, $vSintel, 'mp4', 88000, 0, 9500, 1],
            [2, 1, 1, 'Ep 2: The Encrypted Ledger',    'zr_s1_p.jpg', 'zr_s1_l.jpg', 'Hidden coordinates inside an old ledger point to the harbor.', $vHlsTears, $vHlsTears, $vHlsTears, $vHlsTears, 'm3u8', 734000, 1, 7200, 2],
            [3, 1, 2, 'Ep 1: Return to Colaba',        'zr_s1_p.jpg', 'zr_s1_l.jpg', 'Season 2 opens with a surprise whistleblower stepping forward.', $vMovie300, $vMovie300, $vMovie300, $vMovie300, 'mp4', 120000, 0, 6100, 3],

            // Show 2: Code Zero
            [4, 2, 1, 'Ep 1: Zero Day Breach',         'zr_s2_p.jpg', 'zr_s2_l.jpg', 'An unknown payload slips past the national firewall.', $vHlsMux, $vHlsMux, $vHlsMux, $vHlsMux, 'm3u8', 596000, 0, 8400, 1],
            [5, 2, 1, 'Ep 2: Ghost Server',            'zr_s2_p.jpg', 'zr_s2_l.jpg', 'The team traces the signal to an abandoned data bunker.', $vSintel, $vSintel, $vSintel, $vSintel, 'mp4', 88000, 1, 5900, 2],

            // Show 3: Royal Heist (Rent Show)
            [6, 3, 1, 'Ep 1: The Blueprint',           'zr_s3_p.jpg', 'zr_s3_l.jpg', 'Five specialists gather in Jaipur to study the palace vault.', $vBunny, $vBunny, $vBunny, $vBunny, 'mp4', 596000, 0, 7800, 1],
            [7, 3, 1, 'Ep 2: Vault Seven',             'zr_s3_p.jpg', 'zr_s3_l.jpg', 'Laser grids go offline for ninety seconds at midnight.', $vHlsTears, $vHlsTears, $vHlsTears, $vHlsTears, 'm3u8', 734000, 0, 6400, 2],

            // Show 4: Tiny Explorers (Kids)
            [8, 4, 1, 'Ep 1: Buzzing Honeybees',       'zr_s4_p.jpg', 'zr_s4_l.jpg', 'Discover how bees make golden honey inside their busy hive.', $vBee, $vBee, $vBee, $vBee, 'mp4', 15000, 0, 11200, 1],
            [9, 4, 1, 'Ep 2: Dancing Butterflies',     'zr_s4_p.jpg', 'zr_s4_l.jpg', 'Watch caterpillars transform into colorful butterflies.', $vFly, $vFly, $vFly, $vFly, 'mp4', 18000, 0, 10800, 2],
        ];
        foreach ($episodes as $ep) {
            $epStmt->execute($ep);
        }
    }

    // 16. Shorts & Shorts Episodes (vertical micro-drama / reels)
    if ($tableCount('tbl_shorts') === 0) {
        $vBee      = 'https://flutter.github.io/assets-for-api-docs/assets/videos/bee.mp4';
        $vFly      = 'https://flutter.github.io/assets-for-api-docs/assets/videos/butterfly.mp4';
        $vSintelTr = 'https://media.w3.org/2010/05/sintel/trailer.mp4';
        $vBunnyTr  = 'https://media.w3.org/2010/05/bunny/trailer.mp4';

        $shStmt = $pdo->prepare("
            INSERT INTO tbl_shorts (
                id, type_id, video_type, producer_id, category_id, language_id, cast_id,
                name, storage_type, thumbnail, trailer_storage_type, trailer_type, trailer_url,
                description, is_title, is_comment, is_like, total_view, total_like, avg_rating, total_review, status
            ) VALUES (
                ?, 5, 8, 1, ?, ?, ?,
                ?, 1, ?, 1, 'external', ?,
                ?, 1, 1, 1, ?, ?, 4.8, 5, 1
            )
        ");

        $shorts = [
            [1, '1,2', '1,2', '1,4', '60s Thrills',  'zr_sh1.jpg', $vSintelTr, 'Rapid-fire suspense episodes packed into 60 seconds each.', 18200, 1450],
            [2, '3,6', '1,5', '2,5', 'Comedy Bytes', 'zr_sh2.jpg', $vBunnyTr,  'Hilarious daily relatable sketches and office comedy.',       15400, 1320],
            [3, '7,8', '1,2', '4,6', 'Wild Nature',  'zr_sh3.jpg', $vBee,      'Mesmerizing macro nature clips in crisp HD.',              12900, 1110],
            [4, '5,7', '1,2', '3,5', 'Tech Fast',    'zr_sh4.jpg', $vFly,      'Quick futuristic sci-fi teasers and visual wonders.',      11500, 980],
        ];
        foreach ($shorts as $sh) {
            $shStmt->execute($sh);
        }

        $seStmt = $pdo->prepare("
            INSERT INTO tbl_shorts_episode (
                id, show_id, season_id, name, storage_type, thumbnail, description,
                video_storage_type, video_upload_type, video_320, video_duration,
                is_premium, is_title, total_view, sort_order, status
            ) VALUES (
                ?, ?, 1, ?, 1, ?, ?,
                1, 'external', ?, ?,
                ?, 1, ?, ?, 1
            )
        ");

        $shortEps = [
            [1, 1, 'Part 1: The Last Signal',   'zr_sh1.jpg', 'One phone call changes everything at midnight.', $vSintelTr, 52000, 0, 8100, 1],
            [2, 1, 'Part 2: Rooftop Escape',    'zr_sh1.jpg', 'The chase continues across Mumbai rooftops.',    $vBunnyTr,  33000, 0, 6200, 2],
            [3, 2, 'Part 1: Chai Break Chaos',  'zr_sh2.jpg', 'What happens when the boss joins the tea stall.', $vBunnyTr,  33000, 0, 7400, 1],
            [4, 2, 'Part 2: Monday Standup',    'zr_sh2.jpg', 'Every developer knows this standup meeting!',    $vBee,      15000, 0, 5800, 2],
            [5, 3, 'Part 1: Garden Pollinators','zr_sh3.jpg', 'Close-up of honeybees at work.',                 $vBee,      15000, 0, 6900, 1],
            [6, 3, 'Part 2: Wings of Color',    'zr_sh3.jpg', 'Monarch butterflies gliding in slow motion.',    $vFly,      18000, 0, 5400, 2],
            [7, 4, 'Part 1: Cyber Awakening',   'zr_sh4.jpg', 'First glimpse inside the neon city.',            $vSintelTr, 52000, 0, 5900, 1],
            [8, 4, 'Part 2: Flutter Engine',    'zr_sh4.jpg', 'Smooth 60fps micro-animations in action.',       $vFly,      18000, 0, 4800, 2],
        ];
        foreach ($shortEps as $se) {
            $seStmt->execute($se);
        }
    }

    // 17. Banners (Home Screen & Each Type Tab)
    if ($tableCount('tbl_banner') === 0) {
        $bStmt = $pdo->prepare("INSERT INTO tbl_banner (id, is_home_screen, type_id, video_type, subvideo_type, video_id, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $banners = [
            // Home Screen Banners
            [1, 1, 1, 1, 0, 1,  1], // Free Movie: Sintel
            [2, 1, 2, 2, 0, 1,  2], // Show: Mumbai Chronicles
            [3, 1, 1, 1, 0, 3,  3], // Rent ₹99 Movie: Midnight Protocol
            [4, 1, 4, 7, 1, 12, 4], // Kids Movie: Big Buck Bunny (also ensures Kids Mode has home banner!)

            // Type Tab Banners (Movies=1, Shows=2, LiveTV=3, Kids=4, Shorts=5)
            [5, 2, 1, 1, 0, 2,  1],
            [6, 2, 1, 1, 0, 1,  2],
            [7, 2, 2, 2, 0, 2,  1],
            [8, 2, 2, 2, 0, 3,  2],
            [9, 2, 3, 6, 1, 9,  1],
            [10,2, 3, 6, 1, 10, 2],
            [11,2, 4, 7, 1, 12, 1],
            [12,2, 4, 7, 2, 4,  2],
            [13,2, 5, 8, 0, 1,  1],
        ];
        foreach ($banners as $b) {
            $bStmt->execute($b);
        }
    }

    // 18. Home Sections (All Screen Layouts & All Tabs)
    if ($tableCount('tbl_home_section') === 0) {
        $hsStmt = $pdo->prepare("
            INSERT INTO tbl_home_section (
                id, section_type, is_home_screen, video_type, sub_video_type, type_id,
                title, short_title, screen_layout, content_ids, category_id, language_id, channel_id,
                order_by_upload, order_by_view, premium_video, no_of_content, view_all, is_title, sort_order, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, 0, 0, 0,
                ?, ?, ?, ?, ?, 1, ?, 1
            )
        ");

        $sections = [
            // Home Screen (is_home_screen = 1)
            [1,  0, 1, 101, 0, 1, 'Continue Watching',         'Resume playback',          'landscape',       '',        0, 0, -1, 10, 0, 1],
            [2,  0, 1, 1,   0, 1, 'Top Trending on ZuLive',    'Most watched hits',        'index_portrait',  '',        0, 2, -1, 10, 1, 2],
            [3,  0, 1, 1,   0, 1, 'Watch Free Movies',         'Zero cost HD streaming',   'big_landscape',   '',        2, 0,  0, 10, 1, 3],
            [4,  0, 1, 8,   0, 5, 'ZuLive Shorts',             'Quick vertical stories',   'shorts',          '',        2, 0, -1, 10, 1, 4],
            [5,  0, 1, 2,   0, 2, 'Binge-Worthy Web Series',   'Originals & dramas',       'portrait',        '',        2, 0, -1, 10, 1, 5],
            [6,  0, 1, 103, 0, 1, 'Rent Latest Blockbusters',  'Starting at ₹49',          'square',          '',        2, 0, -1, 10, 1, 6],
            [7,  0, 1, 102, 0, 3, 'Live TV Channels',          '24x7 live entertainment',  'channel',         '',        0, 0, -1, 10, 1, 7],
            [8,  0, 1, 3,   0, 1, 'Browse by Genre',           'Pick your mood',           'category',        '',        0, 0, -1, 10, 1, 8],
            [9,  0, 1, 4,   0, 1, 'Watch in Your Language',    'Hindi, English, Tamil...', 'language',        '',        0, 0, -1, 10, 1, 9],
            [10, 0, 1, 7,   1, 4, 'Kids & Family Zone',        'Safe cartoons & stories',  'landscape',       '',        2, 0, -1, 10, 1, 10],
            [11, 0, 1, 5,   1, 1, 'Coming Soon Trailers',      'Upcoming releases',        'big_portrait',    '',        2, 0, -1, 10, 1, 11],

            // Movies Tab (is_home_screen = 2, type_id = 1)
            [12, 0, 2, 1,   0, 1, 'All Movies on ZuLive',      'Action, thriller & drama', 'portrait',        '',        2, 0, -1, 10, 1, 1],
            [13, 0, 2, 1,   0, 1, 'VIP Exclusive Movies',      'Included with ZuLive VIP', 'big_landscape',   '',        0, 2,  1, 10, 1, 2],
            [14, 0, 2, 103, 0, 1, 'Movies on Rent (₹)',        'Instant 7-14 day access',  'square',          '',        2, 0, -1, 10, 1, 3],

            // TV Shows Tab (is_home_screen = 2, type_id = 2)
            [15, 0, 2, 2,   0, 2, 'Popular Web Series',        'Full seasons streaming',   'big_portrait',    '',        0, 2, -1, 10, 1, 1],
            [16, 0, 2, 103, 0, 2, 'Rent Complete Series (₹)',  'Watch every episode',      'landscape',       '',        2, 0, -1, 10, 1, 2],

            // Live TV Tab (is_home_screen = 2, type_id = 3)
            [17, 0, 2, 6,   1, 3, 'Live Streaming Now',        '24x7 HD feeds',            'big_landscape',   '',        2, 0, -1, 10, 1, 1],
            [18, 0, 2, 102, 0, 3, 'Featured Channels',         'Browse all broadcasters',  'channel',         '',        0, 0, -1, 10, 1, 2],

            // Kids Tab (is_home_screen = 2, type_id = 4)
            [19, 0, 2, 7,   1, 4, 'Kids Animated Movies',      'Free family favorites',    'landscape',       '',        2, 0, -1, 10, 1, 1],
            [20, 0, 2, 7,   2, 4, 'Kids Learning Series',      'Fun episodic stories',     'portrait',        '',        2, 0, -1, 10, 1, 2],

            // Shorts Tab (is_home_screen = 2, type_id = 5)
            [21, 0, 2, 8,   0, 5, 'Trending ZuLive Shorts',    'Watch quick episodes',     'shorts',          '',        0, 2, -1, 10, 1, 1],
        ];
        foreach ($sections as $sec) {
            $hsStmt->execute($sec);
        }
    }

    // 19. Legal Pages (Update empty descriptions with ZuLive content)
    $pages = [
        1 => '<h2>About ZuLive</h2><p>ZuLive is a next-generation Indian OTT streaming platform delivering HD movies, original web series, 24x7 live TV channels, kids entertainment, and vertical Shorts with transparent INR (₹) plans.</p>',
        2 => '<h2>Privacy Policy</h2><p>ZuLive respects your privacy and protects your account, profile preferences, watchlist, and payment history using encrypted authentication and secure storage.</p>',
        3 => '<h2>Terms and Conditions</h2><p>By accessing ZuLive, you agree to stream content for personal, non-commercial viewing according to your selected INR subscription plan or rental period.</p>',
        4 => '<h2>Refund Policy</h2><p>All ZuLive subscriptions and movie rentals are billed in INR (₹). If you experience any billing issue, contact support@zulive.app within 7 days with your transaction ID.</p>',
    ];
    $pStmt = $pdo->prepare("UPDATE tbl_page SET description = ? WHERE id = ? AND (description IS NULL OR description = '')");
    foreach ($pages as $pid => $desc) {
        $pStmt->execute([$desc, $pid]);
    }

    // 20. Sample Notifications
    if ($tableCount('tbl_notification') === 0) {
        $nStmt = $pdo->prepare("INSERT INTO tbl_notification (id, title, message, storage_type, image, status) VALUES (?, ?, ?, 1, ?, 1)");
        $nStmt->execute([1, 'Welcome to ZuLive!', 'Stream movies, series, Live TV & Shorts now. Apply code ZULIVE50 for 50% off any INR plan or rental!', 'zrnotif1.jpg']);
        $nStmt->execute([2, 'New INR Plans & ₹49 Rentals Live', 'Start with our 7-Day Free Trial or upgrade to ZuLive VIP Annual at ₹999/year.', 'zrnotif1.jpg']);
    }

    // 21. Upgrade earlier gradient-seed filenames to real photographic zr* assets (cache-busted & idempotent)
    $pdo->exec("UPDATE tbl_video SET thumbnail = REPLACE(thumbnail, 'seed_', 'zr_'), landscape = REPLACE(landscape, 'seed_', 'zr_') WHERE thumbnail LIKE 'seed_%' OR landscape LIKE 'seed_%'");
    $pdo->exec("UPDATE tbl_tv_show SET thumbnail = REPLACE(thumbnail, 'seed_', 'zr_'), landscape = REPLACE(landscape, 'seed_', 'zr_') WHERE thumbnail LIKE 'seed_%' OR landscape LIKE 'seed_%'");
    $pdo->exec("UPDATE tbl_tv_show_video SET thumbnail = REPLACE(thumbnail, 'seed_', 'zr_'), landscape = REPLACE(landscape, 'seed_', 'zr_') WHERE thumbnail LIKE 'seed_%' OR landscape LIKE 'seed_%'");
    $pdo->exec("UPDATE tbl_shorts SET thumbnail = REPLACE(thumbnail, 'seed_', 'zr_') WHERE thumbnail LIKE 'seed_%'");
    $pdo->exec("UPDATE tbl_shorts_episode SET thumbnail = REPLACE(thumbnail, 'seed_', 'zr_') WHERE thumbnail LIKE 'seed_%'");
    $pdo->exec("UPDATE tbl_category SET image = CONCAT('zr', image) WHERE image LIKE 'cat_%.jpg'");
    $pdo->exec("UPDATE tbl_language SET image = CONCAT('zr', image) WHERE image LIKE 'lang_%.jpg'");
    $pdo->exec("UPDATE tbl_channel SET portrait_img = CONCAT('zr', portrait_img), landscape_img = CONCAT('zr', landscape_img) WHERE portrait_img LIKE 'ch%.jpg'");
    $pdo->exec("UPDATE tbl_cast SET image = CONCAT('zr', image) WHERE image LIKE 'cast%.jpg'");
    $pdo->exec("UPDATE tbl_avatar SET image = CONCAT('zr', image) WHERE image LIKE 'av%.jpg'");
    $pdo->exec("UPDATE tbl_onboarding_screen SET image = CONCAT('zr', image) WHERE image LIKE 'onboard%.jpg'");
    $pdo->exec("UPDATE tbl_notification SET image = CONCAT('zr', image) WHERE image LIKE 'notif%.jpg'");

    $pdo->commit();
    echo "[zulive] Demo dataset verified/seeded successfully (currency=INR ₹).\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "[zulive] Seeder warning: " . $e->getMessage() . "\n");
}

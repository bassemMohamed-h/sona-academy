<?php
/**
 * Seeds the السنة أكاديمي demo: branding, Tutor LMS subjects (topics, lessons,
 * quizzes), pages, primary menu and a demo student with some progress.
 *
 * Safe to re-run — every item is looked up before it is created.
 *
 *   docker compose exec -T wordpress php < scripts/seed-demo.php
 */

define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

use Tutor\Models\CourseModel;
use Tutor\Models\EnrollmentModel;
use Tutor\Models\LessonModel;
use TUTOR\Quiz;
use TUTOR\User;

if (get_stylesheet() !== 'sona-child' || !function_exists('tutor_utils')) {
    fwrite(STDERR, "Activate the Sona Child theme and Tutor LMS first.\n");
    exit(1);
}

$admin = get_users(['role' => 'administrator', 'number' => 1])[0] ?? null;
if (!$admin) {
    fwrite(STDERR, "No administrator account found.\n");
    exit(1);
}
wp_set_current_user($admin->ID);

function say(string $msg): void
{
    echo $msg, "\n";
}

/** Find a post created by this script by its demo key, or insert it. */
function sona_upsert_post(string $key, array $data): int
{
    $found = get_posts([
        'post_type'   => $data['post_type'],
        'post_status' => 'any',
        'numberposts' => 1,
        'meta_key'    => '_sona_demo',
        'meta_value'  => $key,
        'fields'      => 'ids',
    ]);
    if ($found) {
        return (int) $found[0];
    }
    $id = wp_insert_post($data + ['post_status' => 'publish', 'post_author' => get_current_user_id()], true);
    if (is_wp_error($id)) {
        fwrite(STDERR, "Failed to create {$key}: " . $id->get_error_message() . "\n");
        exit(1);
    }
    update_post_meta($id, '_sona_demo', $key);
    return $id;
}

/** Import an image (a GD image, or an existing file copied as-is) into the media library once. */
function sona_upsert_image(string $key, GdImage|string $img, string $filename, string $title): int
{
    $found = get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_sona_demo', 'meta_value' => $key, 'fields' => 'ids']);
    if ($found) {
        return (int) $found[0];
    }
    $tmp = wp_tempnam($filename);
    if ($img instanceof GdImage) {
        imagesavealpha($img, true);
        imagepng($img, $tmp);
    } else {
        copy($img, $tmp);
    }
    $id = media_handle_sideload(['name' => $filename, 'tmp_name' => $tmp], 0, $title);
    if (is_wp_error($id)) {
        fwrite(STDERR, "Image import failed for {$key}: " . $id->get_error_message() . "\n");
        exit(1);
    }
    update_post_meta($id, '_sona_demo', $key);
    return $id;
}

function sona_canvas(int $w, int $h, string $hex): GdImage
{
    $img = imagecreatetruecolor($w, $h);
    imagesavealpha($img, true);
    [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
    imagefill($img, 0, 0, imagecolorallocate($img, $r, $g, $b));
    return $img;
}

/** Palm logo placed on a solid background; $scale is logo width relative to canvas width. */
function sona_logo_on(int $w, int $h, string $bg, float $scale, bool $mirror = false): GdImage
{
    $logo = imagecreatefrompng(get_stylesheet_directory() . '/assets/img/logo.png');
    if ($mirror) {
        imageflip($logo, IMG_FLIP_HORIZONTAL);
    }
    $img = sona_canvas($w, $h, $bg);
    $lw  = (int) ($w * $scale);
    $lh  = (int) ($lw * imagesy($logo) / imagesx($logo));
    imagecopyresampled($img, $logo, (int) (($w - $lw) / 2), (int) (($h - $lh) / 2), 0, 0, $lw, $lh, imagesx($logo), imagesy($logo));
    return $img;
}

// ---------------------------------------------------------------------------
// Branding
// ---------------------------------------------------------------------------
update_option('blogname', 'السنة أكاديمي');
update_option('blogdescription', 'أكاديمية لتعلّم أساسيات الإسلام');

$logo_id = sona_upsert_image('logo-v2', get_stylesheet_directory() . '/assets/img/logo.png', 'sona-logo.png', 'شعار السنة أكاديمي');
set_theme_mod('custom_logo', $logo_id);

$icon_id = sona_upsert_image('site-icon', sona_logo_on(512, 512, '#F5F8F3', 0.9), 'sona-icon.png', 'أيقونة الموقع');
update_option('site_icon', $icon_id);

$covers = [
    sona_upsert_image('cover-mint', sona_logo_on(1200, 675, '#F5F8F3', 0.62), 'sona-cover-mint.png', 'غلاف مادة'),
    sona_upsert_image('cover-green', sona_logo_on(1200, 675, '#1C4A24', 0.62, true), 'sona-cover-green.png', 'غلاف مادة'),
    sona_upsert_image('cover-sage', sona_logo_on(1200, 675, '#E6EEE3', 0.62, true), 'sona-cover-sage.png', 'غلاف مادة'),
];
say('✓ Branding: title, logo, site icon, course covers');

// ---------------------------------------------------------------------------
// Tutor LMS settings
// ---------------------------------------------------------------------------
$tutor_options = (array) get_option('tutor_option', []);
unset($tutor_options['tutor_primary_color']);
$tutor_options['brand_color']                = '#2F7D32';
$tutor_options['monetize_by']                = 'free'; // the academy is completely free
$tutor_options['show_dashboard_site_header'] = 1;
$tutor_options['show_dashboard_site_footer'] = 0;
update_option('tutor_option', $tutor_options);
say('✓ Tutor: brand colour #2F7D32, site header on dashboard, monetization off');

// ---------------------------------------------------------------------------
// Subjects → Tutor courses
// ---------------------------------------------------------------------------
$term = term_exists(SONA_SUBJECT_CATEGORY, 'course-category')
    ?: wp_insert_term('المواد الدراسية', 'course-category', ['slug' => SONA_SUBJECT_CATEGORY]);
$term_id = (int) $term['term_id'];

const DEMO_NOTE = '<p><em>محتوى تجريبي للعرض — يُستبدل بالدرس المسجّل.</em></p>';

$curriculum = [
    'aqeedah' => [
        'lessons' => [
            'معنى العقيدة وأهميتها' => 'العقيدة هي ما يعتقده المسلم ويؤمن به إيمانًا جازمًا، وهي الأساس الذي تُبنى عليه العبادات والمعاملات.',
            'مصادر التلقي'          => 'يتلقى المسلم عقيدته من القرآن الكريم والسنة النبوية الصحيحة، على فهم الصحابة رضي الله عنهم.',
            'أركان الإيمان'         => 'أركان الإيمان ستة: الإيمان بالله، وملائكته، وكتبه، ورسله، واليوم الآخر، والقدر خيره وشره.',
        ],
        'quiz' => [
            ['كم عدد أركان الإيمان؟', ['ستة', 'خمسة', 'سبعة']],
            ['أيٌّ مما يلي من أركان الإيمان؟', ['الإيمان بالملائكة', 'صيام رمضان', 'الحج']],
            ['ما المصدر الأول لتلقي العقيدة؟', ['القرآن الكريم', 'كتب التاريخ', 'العادات والتقاليد']],
            ['الإيمان بالقدر خيره وشره:', ['ركن من أركان الإيمان', 'سنّة مستحبة', 'ليس من الإيمان']],
        ],
    ],
    'tafsir' => [
        'lessons' => [
            'مدخل إلى علم التفسير' => 'التفسير هو بيان معاني القرآن الكريم، وهو من أشرف العلوم لتعلّقه بكلام الله تعالى.',
            'تفسير سورة الفاتحة'   => 'سورة الفاتحة أم الكتاب، وهي سبع آيات، يقرؤها المسلم في كل ركعة من صلاته.',
            'تفسير قصار السور'     => 'نتناول في هذا الدرس معاني سور الإخلاص والفلق والناس وما فيها من التوحيد والاستعاذة بالله.',
        ],
        'quiz' => [
            ['ما معنى التفسير؟', ['بيان معاني القرآن الكريم', 'حفظ القرآن الكريم', 'تحسين تلاوة القرآن']],
            ['ما أول سورة في ترتيب المصحف؟', ['الفاتحة', 'البقرة', 'الناس']],
            ['كم عدد آيات سورة الفاتحة؟', ['سبع', 'خمس', 'ست']],
            ['ما آخر سورة في ترتيب المصحف؟', ['الناس', 'الفلق', 'الإخلاص']],
        ],
    ],
    'hadith' => [
        'lessons' => [
            'تعريف الحديث وأقسامه'        => 'الحديث هو ما أضيف إلى النبي ﷺ من قول أو فعل أو تقرير أو صفة، ومنه الصحيح والحسن والضعيف.',
            'حديث: «إنما الأعمال بالنيات»' => 'حديث عظيم يبيّن أن قبول الأعمال وثوابها مرتبط بالنية الصالحة والإخلاص لله تعالى.',
            'أحاديث في الأخلاق'           => 'نماذج من أحاديث النبي ﷺ في الصدق والأمانة وحسن الخلق مع الناس.',
        ],
        'quiz' => [
            ['الحديث النبوي هو:', ['ما أضيف إلى النبي ﷺ من قول أو فعل أو تقرير أو صفة', 'كلام العلماء في الفقه', 'قصص الأنبياء السابقين']],
            ['حديث «إنما الأعمال بالنيات» يدل على أهمية:', ['النية', 'الصيام', 'الزكاة']],
            ['أصح كتب الحديث بعد القرآن الكريم:', ['صحيح البخاري', 'كتب السير', 'كتب اللغة']],
            ['الحديث الصحيح:', ['يُحتج به في الدين', 'لا يُعمل به', 'يُقرأ للتسلية فقط']],
        ],
    ],
    'seerah' => [
        'lessons' => [
            'مولد النبي ﷺ ونشأته'            => 'وُلد النبي ﷺ في مكة المكرمة في عام الفيل، ونشأ يتيمًا، وعُرف بالصادق الأمين.',
            'البعثة والدعوة في مكة'          => 'نزل الوحي على النبي ﷺ في غار حراء، فبدأ دعوته سرًّا ثم جهرًا، وصبر هو وأصحابه على الأذى.',
            'الهجرة وبناء المجتمع في المدينة' => 'هاجر النبي ﷺ إلى المدينة المنورة، فبنى المسجد وآخى بين المهاجرين والأنصار.',
        ],
        'quiz' => [
            ['في أي مدينة وُلد النبي ﷺ؟', ['مكة المكرمة', 'المدينة المنورة', 'الطائف']],
            ['في أي غار نزل الوحي أول مرة؟', ['غار حراء', 'غار ثور', 'لم ينزل في غار']],
            ['إلى أين هاجر النبي ﷺ؟', ['المدينة المنورة', 'الشام', 'اليمن']],
            ['بماذا عُرف النبي ﷺ قبل البعثة؟', ['الصادق الأمين', 'الشاعر', 'التاجر الغني']],
        ],
    ],
    'fiqh' => [
        'lessons' => [
            'الطهارة وأحكامها'         => 'الطهارة شرط لصحة الصلاة، وتكون بالماء الطهور، وتشمل الطهارة من الحدث ومن النجاسة.',
            'الوضوء'                   => 'نتعلم في هذا الدرس فرائض الوضوء وسننه وصفته كما جاءت في السنة النبوية.',
            'الصلاة: شروطها وأركانها'  => 'الصلوات المفروضة خمس في اليوم والليلة، ولها شروط وأركان وواجبات نتعرّف عليها بأسلوب مبسّط.',
        ],
        'quiz' => [
            ['كم عدد الصلوات المفروضة في اليوم والليلة؟', ['خمس', 'ثلاث', 'ست']],
            ['كم عدد ركعات صلاة الفجر؟', ['ركعتان', 'ثلاث ركعات', 'أربع ركعات']],
            ['كم عدد ركعات صلاة المغرب؟', ['ثلاث ركعات', 'ركعتان', 'أربع ركعات']],
            ['الطهارة بالنسبة للصلاة:', ['شرط لصحتها', 'سنّة مستحبة', 'لا علاقة لها بالصلاة']],
        ],
    ],
    'tarbiyah' => [
        'lessons' => [
            'حقوق الوالدين'            => 'برّ الوالدين من أعظم الواجبات، ويكون بالإحسان إليهما وطاعتهما في المعروف والدعاء لهما.',
            'آداب الطعام والشراب'      => 'من آداب الطعام التسمية في أوله، والأكل باليمين، ومما يلي الآكل، وحمد الله في آخره.',
            'آداب السلام والاستئذان'   => 'إفشاء السلام من أسباب المحبة بين المسلمين، والاستئذان أدب يحفظ حرمات البيوت.',
        ],
        'quiz' => [
            ['برّ الوالدين:', ['واجب', 'مكروه', 'مباح فقط']],
            ['ماذا يقول المسلم قبل الأكل؟', ['بسم الله', 'الحمد لله', 'لا يقول شيئًا']],
            ['بأي يد يأكل المسلم؟', ['باليمين', 'باليسار', 'لا فرق']],
            ['ردّ السلام:', ['واجب', 'مكروه', 'غير مشروع']],
        ],
    ],
    'arabic' => [
        'lessons' => [
            'أقسام الكلام'     => 'الكلام في اللغة العربية ثلاثة أقسام: اسم، وفعل، وحرف جاء لمعنى.',
            'الاسم وعلاماته'   => 'من علامات الاسم: قبول «ال» التعريف، والتنوين، ودخول حروف الجر عليه.',
            'الفعل والحرف'     => 'الفعل ما دلّ على حدث مقترن بزمن، والحرف ما لا يظهر معناه إلا مع غيره.',
        ],
        'quiz' => [
            ['أقسام الكلام في اللغة العربية:', ['اسم وفعل وحرف', 'اسم وفعل فقط', 'فعل وحرف فقط']],
            ['كلمة «كَتَبَ» من نوع:', ['فعل', 'اسم', 'حرف']],
            ['كلمة «في» من نوع:', ['حرف', 'اسم', 'فعل']],
            ['كلمة «مسجد» من نوع:', ['اسم', 'فعل', 'حرف']],
        ],
    ],
];

global $wpdb;
$courses = [];

foreach (sona_subjects() as $i => $subject) {
    $slug = $subject['slug'];
    $plan = $curriculum[$slug];

    $course_id = sona_upsert_post("course-{$slug}", [
        'post_type'    => 'courses',
        'post_title'   => $subject['name'],
        'post_name'    => $slug,
        'post_excerpt' => $subject['desc'],
        'post_content' => '<p>' . esc_html($subject['desc']) . '</p><p>مادة من مواد الفصل الأول في السنة أكاديمي، تُدرَّس بالعربية مع ترجمة، وتنتهي باختبار ودرجة من ١٠٠.</p>',
        'menu_order'   => $i,
    ]);
    wp_set_object_terms($course_id, [$term_id], 'course-category');
    set_post_thumbnail($course_id, $covers[$i % count($covers)]);
    foreach ([
        '_tutor_course_price_type'           => 'free',
        '_tutor_course_level'                => 'beginner',
        '_tutor_course_duration_hours'       => 3,
        '_tutor_course_duration_minutes'     => 0,
        '_course_duration'                   => ['hours' => '3', 'minutes' => '0'],
        '_tutor_enable_qa'                   => 'yes',
        '_tutor_is_public_course'            => 'no',
        '_tutor_course_benefits'             => implode("\n", array_keys($plan['lessons'])),
        '_tutor_course_target_audience'      => "المبتدئون في طلب العلم الشرعي\nمن يريد تعلّم أساسيات دينه بالعربية",
        '_tutor_course_material_includes'    => "دروس مسجّلة\nاختبار في نهاية المادة",
    ] as $meta_key => $meta_value) {
        update_post_meta($course_id, $meta_key, $meta_value);
    }

    $topic_id = sona_upsert_post("topic-{$slug}", [
        'post_type'    => 'topics',
        'post_title'   => 'الوحدة الأولى',
        'post_content' => 'دروس الوحدة الأولى من مادة ' . $subject['name'],
        'post_parent'  => $course_id,
        'menu_order'   => 0,
    ]);

    $lesson_ids = [];
    $n = 0;
    foreach ($plan['lessons'] as $title => $body) {
        $lesson_ids[] = sona_upsert_post("lesson-{$slug}-{$n}", [
            'post_type'    => 'lesson',
            'post_title'   => $title,
            'post_content' => '<p>' . esc_html($body) . '</p>' . DEMO_NOTE,
            'post_parent'  => $topic_id,
            'menu_order'   => $n,
        ]);
        $n++;
    }

    $quiz_id = sona_upsert_post("quiz-{$slug}", [
        'post_type'    => 'tutor_quiz',
        'post_title'   => 'اختبار مادة ' . $subject['name'],
        'post_content' => 'اختبار قصير من ٤ أسئلة، لكل سؤال ٢٥ درجة.',
        'post_parent'  => $topic_id,
        'menu_order'   => $n,
    ]);
    update_post_meta($quiz_id, 'tutor_quiz_option', array_merge(Quiz::get_default_quiz_settings(), ['passing_grade' => 60, 'questions_order' => 'sorting']));

    $has_questions = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tutor_quiz_questions WHERE quiz_id = %d", $quiz_id));
    if (!$has_questions) {
        foreach ($plan['quiz'] as $q => [$question, $answers]) {
            $wpdb->insert("{$wpdb->prefix}tutor_quiz_questions", [
                'quiz_id'              => $quiz_id,
                'question_title'       => $question,
                'question_description' => '',
                'answer_explanation'   => '',
                'question_type'        => 'single_choice',
                'question_mark'        => 25,
                'question_settings'    => maybe_serialize(array_merge(Quiz::get_default_question_settings('single_choice'), ['question_mark' => 25])),
                'question_order'       => $q + 1,
            ]);
            $question_id = $wpdb->insert_id;
            foreach ($answers as $a => $answer) {
                $wpdb->insert("{$wpdb->prefix}tutor_quiz_question_answers", [
                    'belongs_question_id'   => $question_id,
                    'belongs_question_type' => 'single_choice',
                    'answer_title'          => $answer,
                    'is_correct'            => $a === 0 ? 1 : 0, // first listed answer is correct
                    'image_id'              => 0,
                    'answer_two_gap_match'  => '',
                    'answer_view_format'    => 'text',
                    'answer_order'          => $a + 1,
                ]);
            }
        }
    }

    $courses[$slug] = ['id' => $course_id, 'lessons' => $lesson_ids, 'quiz' => $quiz_id];
}
say('✓ Subjects: ' . count($courses) . ' Tutor courses, each with 3 lessons and a 4-question quiz');

// ---------------------------------------------------------------------------
// Free academy: every course free, no cart / checkout pages
// ---------------------------------------------------------------------------
foreach (get_posts(['post_type' => 'courses', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids']) as $cid) {
    update_post_meta($cid, '_tutor_course_price_type', 'free');
}
foreach (['tutor_cart_page_id', 'tutor_checkout_page_id'] as $opt) {
    $page_id = (int) ($tutor_options[$opt] ?? 0);
    if ($page_id && get_post_status($page_id) && get_post_status($page_id) !== 'trash') {
        wp_trash_post($page_id);
    }
    unset($tutor_options[$opt]);
}
update_option('tutor_option', $tutor_options);
say('✓ Free academy: all courses free, cart & checkout pages moved to trash');

// ---------------------------------------------------------------------------
// Pages
// ---------------------------------------------------------------------------
$pages = [
    'about'    => ['عن الأكاديمية', '<!-- wp:paragraph --><p>السنة أكاديمي أكاديمية على الإنترنت لتعلّم أساسيات الإسلام بالعربية، بدروس مسجّلة على يد مختصين مع ترجمة إلى لغات متعددة.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>يدرس الطالب في كل فصل سبع مواد: العقيدة، والتفسير، والحديث، والسيرة، والفقه، والتربية الإسلامية، واللغة العربية.</p><!-- /wp:paragraph -->'],
    'faq'      => ['الأسئلة الشائعة', '<!-- wp:heading {"level":3} --><h3>هل الدراسة مجانية؟</h3><!-- /wp:heading --><!-- wp:paragraph --><p>نعم، التسجيل مجاني ويكفي إنشاء حساب ببريدك الإلكتروني.</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3>كيف تُحسب الدرجات؟</h3><!-- /wp:heading --><!-- wp:paragraph --><p>لكل مادة اختبار في نهايتها من ٤ أسئلة، لكل سؤال ٢٥ درجة، فتكون الدرجة الكاملة ١٠٠. تُحتسب أعلى محاولة لك، ودرجة النجاح ٦٠.</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3>متى أحصل على الإفادة؟</h3><!-- /wp:heading --><!-- wp:paragraph --><p>عند إتمام جميع مواد الفصل بنجاح، يمكنك طلب إفادة التسجيل من ملفك الشخصي.</p><!-- /wp:paragraph -->'],
    'schedule' => ['الجدول', '<!-- wp:paragraph --><p>مواعيد البث المباشر الأسبوعي لمراجعة الدروس والإجابة عن الأسئلة.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[sona_schedule]<!-- /wp:shortcode -->'],
    'library'  => ['المكتبة', '<!-- wp:paragraph --><p>ملفات مساندة لكل مادة: ملخصات الدروس، والمتون، وأوراق المراجعة. <em>(محتوى تجريبي — تُضاف الملفات لاحقًا.)</em></p><!-- /wp:paragraph -->'],
    'contact'  => ['تواصل معنا', '<!-- wp:paragraph --><p>لأي استفسار عن الدراسة أو التسجيل، راسلنا عبر البريد الإلكتروني للدعم الظاهر في أسفل الصفحة، وسنرد عليك في أقرب وقت.</p><!-- /wp:paragraph -->'],
];
$page_ids = [];
foreach ($pages as $slug => [$title, $content]) {
    $page_ids[$slug] = sona_upsert_post("page-{$slug}", [
        'post_type'    => 'page',
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_content' => $content,
    ]);
    update_post_meta($page_ids[$slug], 'site-sidebar-layout', 'no-sidebar');
}
say('✓ Pages: ' . implode(', ', array_keys($page_ids)));

// ---------------------------------------------------------------------------
// Primary menu
// ---------------------------------------------------------------------------
$menu_name = 'السنة أكاديمي — الرئيسية';
$menu      = wp_get_nav_menu_object($menu_name);
$menu_id   = $menu ? $menu->term_id : wp_create_nav_menu($menu_name);
if (!wp_get_nav_menu_items($menu_id)) {
    $items = [
        ['menu-item-title' => 'الرئيسية', 'menu-item-url' => home_url('/'), 'menu-item-type' => 'custom'],
        ['menu-item-title' => 'المواد الدراسية', 'menu-item-url' => get_post_type_archive_link('courses'), 'menu-item-type' => 'custom'],
        ['menu-item-title' => 'الجدول', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_ids['schedule'], 'menu-item-type' => 'post_type'],
        ['menu-item-title' => 'المكتبة', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_ids['library'], 'menu-item-type' => 'post_type'],
        ['menu-item-title' => 'تواصل معنا', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_ids['contact'], 'menu-item-type' => 'post_type'],
    ];
    foreach ($items as $pos => $item) {
        wp_update_nav_menu_item($menu_id, 0, $item + ['menu-item-status' => 'publish', 'menu-item-position' => $pos + 1]);
    }
}
$locations                 = get_theme_mod('nav_menu_locations', []);
$locations['sona-primary'] = $menu_id;
set_theme_mod('nav_menu_locations', $locations);
say('✓ Menu assigned to the header');

// ---------------------------------------------------------------------------
// Demo student with progress
// ---------------------------------------------------------------------------
$student = get_user_by('login', 'student');
if (!$student) {
    $student_id = wp_insert_user([
        'user_login'   => 'student',
        'user_pass'    => 'student',
        'user_email'   => 'student@example.com',
        'display_name' => 'عبد الله أحمد',
        'first_name'   => 'عبد الله',
        'last_name'    => 'أحمد',
        'role'         => 'subscriber',
        'locale'       => 'ar',
    ]);
    if (is_wp_error($student_id)) {
        fwrite(STDERR, 'Student creation failed: ' . $student_id->get_error_message() . "\n");
        exit(1);
    }
} else {
    $student_id = $student->ID;
}
update_user_meta($student_id, 'sona_translation_lang', 'English');
update_user_meta($student_id, User::TOUR_COMPLETED_META, 1);

// slug => [lessons completed, quiz score or null]
$progress = [
    'aqeedah' => [3, 100],
    'tafsir'  => [3, 75],
    'hadith'  => [3, 75],
    'seerah'  => [2, null],
    'fiqh'    => [1, null],
];

foreach ($courses as $slug => $c) {
    EnrollmentModel::do_enroll($c['id'], 0, $student_id);

    [$done, $score] = $progress[$slug] ?? [0, null];
    foreach (array_slice($c['lessons'], 0, $done) as $lesson_id) {
        if (!get_user_meta($student_id, '_tutor_completed_lesson_id_' . $lesson_id, true)) {
            LessonModel::mark_lesson_complete($lesson_id, $student_id);
        }
    }

    if ($score !== null) {
        $has_attempt = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_quiz_attempts WHERE quiz_id = %d AND user_id = %d",
            $c['quiz'],
            $student_id
        ));
        if (!$has_attempt) {
            $now = current_time('mysql');
            $wpdb->insert("{$wpdb->prefix}tutor_quiz_attempts", [
                'course_id'                => $c['id'],
                'quiz_id'                  => $c['quiz'],
                'user_id'                  => $student_id,
                'total_questions'          => 4,
                'total_answered_questions' => 4,
                'total_marks'              => 100,
                'earned_marks'             => $score,
                'attempt_info'             => maybe_serialize(get_post_meta($c['quiz'], 'tutor_quiz_option', true)),
                'attempt_status'           => 'attempt_ended',
                'attempt_ip'               => '127.0.0.1',
                'attempt_started_at'       => $now,
                'attempt_ended_at'         => $now,
                'is_manually_reviewed'     => 0,
                'result'                   => $score >= 60 ? 'pass' : 'fail',
            ]);
        }
        if (!tutor_utils()->is_completed_course($c['id'], $student_id)) {
            CourseModel::mark_course_as_completed($c['id'], $student_id);
        }
    }
}
say("✓ Demo student: login \"student\" / password \"student\", enrolled in all subjects with sample progress");

flush_rewrite_rules(false); // soft: a hard flush from the CLI blanks .htaccess
say('Done. Open ' . home_url('/'));

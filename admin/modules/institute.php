<?php
require_once '../includes/config.php';

// Fetch institute details (assuming single institute for now)
$stmt = $pdo->query("SELECT * FROM institutes LIMIT 1");
$institute = $stmt->fetch();

// Success/Error Messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $short_name = $_POST['short_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'] ?? '';
    $website = $_POST['website'] ?? '';
    $established_year = $_POST['established_year'];
    $about_text = $_POST['about_text'];
    $vision = $_POST['vision'];
    $mission = $_POST['mission'];
    $facebook_url = $_POST['facebook_url'];
    $instagram_url = $_POST['instagram_url'];
    $linkedin_url = $_POST['linkedin_url'];
    $twitter_url = $_POST['twitter_url'];

    // New Fields
    $principal_name = $_POST['principal_name'] ?? '';
    $principal_title = $_POST['principal_title'] ?? 'Principal';
    $principal_message = $_POST['principal_message'] ?? '';
    $chairman_name = $_POST['chairman_name'] ?? '';
    $chairman_title = $_POST['chairman_title'] ?? 'Chairman';
    $chairman_message = $_POST['chairman_message'] ?? '';
    $history_title = $_POST['history_title'] ?? 'Our Legacy';
    $history_text = $_POST['history_text'] ?? '';
    $virtual_tour_embed = $_POST['virtual_tour_embed'] ?? '';
    $admission_cta_text = $_POST['admission_cta_text'] ?? 'Apply for Admission';
    $admission_cta_link = $_POST['admission_cta_link'] ?? 'admissions.php';
    $student_counter = $_POST['student_counter'] ?? 0;
    $faculty_counter = $_POST['faculty_counter'] ?? 0;
    $placement_counter = $_POST['placement_counter'] ?? 0;
    $course_counter = $_POST['course_counter'] ?? 0;
    $news_ticker_text = $_POST['news_ticker_text'] ?? '';
    
    // Handle File Uploads
    $upload_dir = '../assets/img/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $logo_path = $institute['logo'] ?? '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $logo_path = 'assets/img/uploads/logo_' . time() . '.' . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['logo']['tmp_name'], '../' . $logo_path);
    }

    $principal_photo = $institute['principal_photo'] ?? '';
    if (isset($_FILES['principal_photo']) && $_FILES['principal_photo']['error'] === 0) {
        $principal_photo = 'assets/img/uploads/principal_' . time() . '.' . pathinfo($_FILES['principal_photo']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['principal_photo']['tmp_name'], '../' . $principal_photo);
    }

    $chairman_photo = $institute['chairman_photo'] ?? '';
    if (isset($_FILES['chairman_photo']) && $_FILES['chairman_photo']['error'] === 0) {
        $chairman_photo = 'assets/img/uploads/chairman_' . time() . '.' . pathinfo($_FILES['chairman_photo']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['chairman_photo']['tmp_name'], '../' . $chairman_photo);
    }

    $org_chart_image = $institute['org_chart_image'] ?? '';
    if (isset($_FILES['org_chart_image']) && $_FILES['org_chart_image']['error'] === 0) {
        $org_chart_image = 'assets/img/uploads/org_chart_' . time() . '.' . pathinfo($_FILES['org_chart_image']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['org_chart_image']['tmp_name'], '../' . $org_chart_image);
    }

    // Handle Slider Upload
    if (isset($_FILES['slider_image']) && $_FILES['slider_image']['error'] === 0) {
        $slider_path = 'assets/img/uploads/banner_' . time() . '.' . pathinfo($_FILES['slider_image']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['slider_image']['tmp_name'], '../' . $slider_path)) {
            $slider_title = $_POST['slider_title'] ?? '';
            $pdo->prepare("INSERT INTO home_sliders (image_path, caption_title) VALUES (?, ?)")->execute([$slider_path, $slider_title]);
        }
    }

    // Quick Link Handling
    if (!empty($_POST['quick_link_title']) && !empty($_POST['quick_link_url'])) {
        $pdo->prepare("INSERT INTO quick_links (title, link_url) VALUES (?, ?)")->execute([$_POST['quick_link_title'], $_POST['quick_link_url']]);
    }

    // Accreditation Handling
    if (!empty($_POST['accred_name']) && isset($_FILES['accred_logo']) && $_FILES['accred_logo']['error'] === 0) {
        $accred_logo = 'assets/img/uploads/accred_' . time() . '.' . pathinfo($_FILES['accred_logo']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['accred_logo']['tmp_name'], '../' . $accred_logo);
        $pdo->prepare("INSERT INTO accreditations (name, logo_path) VALUES (?, ?)")->execute([$_POST['accred_name'], $accred_logo]);
    }

    try {
        if ($institute) {
            $sql = "UPDATE institutes SET 
                    name = ?, short_name = ?, email = ?, phone = ?, address = ?, 
                    city = ?, state = ?, pincode = ?, website = ?, established_year = ?, 
                    about_text = ?, vision = ?, mission = ?, logo = ?,
                    facebook_url = ?, instagram_url = ?, linkedin_url = ?, twitter_url = ?,
                    principal_name = ?, principal_title = ?, principal_message = ?, principal_photo = ?,
                    chairman_name = ?, chairman_title = ?, chairman_message = ?, chairman_photo = ?,
                    history_title = ?, history_text = ?, org_chart_image = ?, virtual_tour_embed = ?,
                    admission_cta_text = ?, admission_cta_link = ?,
                    student_counter = ?, faculty_counter = ?, placement_counter = ?, course_counter = ?,
                    news_ticker_text = ?
                    WHERE id = ?";
            $pdo->prepare($sql)->execute([
                $name, $short_name, $email, $phone, $address, 
                $city, $state, $pincode, $website, $established_year, 
                $about_text, $vision, $mission, $logo_path,
                $facebook_url, $instagram_url, $linkedin_url, $twitter_url,
                $principal_name, $principal_title, $principal_message, $principal_photo,
                $chairman_name, $chairman_title, $chairman_message, $chairman_photo,
                $history_title, $history_text, $org_chart_image, $virtual_tour_embed,
                $admission_cta_text, $admission_cta_link,
                $student_counter, $faculty_counter, $placement_counter, $course_counter,
                $news_ticker_text,
                $institute['id']
            ]);
        }
        header("Location: institute.php?success=Institute details updated successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Slider Deletion
if (isset($_GET['delete_slide'])) {
    $id = $_GET['delete_slide'];
    $stmt = $pdo->prepare("SELECT image_path FROM home_sliders WHERE id = ?");
    $stmt->execute([$id]);
    $slide = $stmt->fetch();
    if ($slide) {
        if (file_exists('../' . $slide['image_path'])) {
            unlink('../' . $slide['image_path']);
        }
        $pdo->prepare("DELETE FROM home_sliders WHERE id = ?")->execute([$id]);
        header("Location: institute.php?success=Slider image removed");
        exit();
    }
}

// Handle Quick Link Deletion
if (isset($_GET['delete_qlink'])) {
    $id = $_GET['delete_qlink'];
    $pdo->prepare("DELETE FROM quick_links WHERE id = ?")->execute([$id]);
    header("Location: institute.php?success=Quick link removed");
    exit();
}

// Handle Accreditation Deletion
if (isset($_GET['delete_accred'])) {
    $id = $_GET['delete_accred'];
    $stmt = $pdo->prepare("SELECT logo_path FROM accreditations WHERE id = ?");
    $stmt->execute([$id]);
    $ac = $stmt->fetch();
    if ($ac) {
        if (file_exists('../' . $ac['logo_path'])) {
            unlink('../' . $ac['logo_path']);
        }
        $pdo->prepare("DELETE FROM accreditations WHERE id = ?")->execute([$id]);
        header("Location: institute.php?success=Accreditation removed");
        exit();
    }
}

$page_title = 'Institute Management';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700;">Institute Profile</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Manage the core details of the institution.</p>
    </div>

    <?php if ($success): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="institute.php" method="POST" enctype="multipart/form-data">
        <div class="card">
            <!-- Tabs Navigation -->
            <div style="display: flex; gap: 2rem; border-bottom: 1px solid var(--border); margin-bottom: 2rem; overflow-x: auto;">
                <button type="button" class="tab-btn active" onclick="showTab('basic')">General Info</button>
                <button type="button" class="tab-btn" onclick="showTab('home')">Home Features</button>
                <button type="button" class="tab-btn" onclick="showTab('about')">About Us Content</button>
                <button type="button" class="tab-btn" onclick="showTab('sliders')">Hero Banners</button>
                <button type="button" class="tab-btn" onclick="showTab('social')">Social & Links</button>
            </div>

            <!-- Basic Tab -->
            <div id="tab-basic" class="tab-content active">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label>Institute Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $institute['name'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Short Name (Abbreviation)</label>
                        <input type="text" name="short_name" class="form-control" value="<?php echo $institute['short_name'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $institute['email'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo $institute['phone'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Full Address</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo $institute['address'] ?? ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" class="form-control" value="<?php echo $institute['city'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state" class="form-control" value="<?php echo $institute['state'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Pincode</label>
                        <input type="text" name="pincode" class="form-control" value="<?php echo $institute['pincode'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Website URL</label>
                        <input type="text" name="website" class="form-control" value="<?php echo $institute['website'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Established Year</label>
                        <input type="number" name="established_year" class="form-control" value="<?php echo $institute['established_year'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Institute Logo</label>
                        <input type="file" name="logo" class="form-control">
                        <?php if (isset($institute['logo'])): ?>
                            <img src="<?php echo '../' . $institute['logo']; ?>" style="height: 50px; margin-top: 10px;">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Home Features Tab -->
            <div id="tab-home" class="tab-content" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group" style="grid-column: span 2;">
                        <label><i data-lucide="megaphone"></i> News Ticker / Rotating Announcements</label>
                        <textarea name="news_ticker_text" class="form-control" rows="2" placeholder="Enter news items separated by |"><?php echo $institute['news_ticker_text'] ?? ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Admission CTA Text</label>
                        <input type="text" name="admission_cta_text" class="form-control" value="<?php echo $institute['admission_cta_text'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Admission CTA Link</label>
                        <input type="text" name="admission_cta_link" class="form-control" value="<?php echo $institute['admission_cta_link'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Student Counter</label>
                        <input type="number" name="student_counter" class="form-control" value="<?php echo $institute['student_counter'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Faculty Counter</label>
                        <input type="number" name="faculty_counter" class="form-control" value="<?php echo $institute['faculty_counter'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Placement Counter</label>
                        <input type="number" name="placement_counter" class="form-control" value="<?php echo $institute['placement_counter'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Course Counter</label>
                        <input type="number" name="course_counter" class="form-control" value="<?php echo $institute['course_counter'] ?? ''; ?>">
                    </div>
                </div>

                <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem;">Quick Access Links</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 100px; gap: 1rem; margin-bottom: 1rem;">
                        <input type="text" name="quick_link_title" class="form-control" placeholder="Link Title (e.g. ERP Login)">
                        <input type="text" name="quick_link_url" class="form-control" placeholder="URL (e.g. https://...)">
                        <button type="submit" class="btn btn-outline" style="height: 42px;">Add</button>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <?php
                        $qlinks = $pdo->query("SELECT * FROM quick_links WHERE is_active = 1")->fetchAll();
                        foreach ($qlinks as $ql): ?>
                            <div style="background: #f1f5f9; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; display: flex; align-items: center; gap: 8px;">
                                <?php echo $ql['title']; ?>
                                <a href="?delete_qlink=<?php echo $ql['id']; ?>" style="color: var(--danger);"><i data-lucide="x" size="14"></i></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem;">Accreditation Badges</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 100px; gap: 1rem; margin-bottom: 1rem;">
                        <input type="text" name="accred_name" class="form-control" placeholder="Accreditation Name (e.g. NAAC A+)">
                        <input type="file" name="accred_logo" class="form-control">
                        <button type="submit" class="btn btn-outline" style="height: 42px;">Add</button>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px;">
                        <?php
                        $accreds = $pdo->query("SELECT * FROM accreditations WHERE is_active = 1")->fetchAll();
                        foreach ($accreds as $ac): ?>
                            <div class="card" style="padding: 10px; text-align: center;">
                                <img src="../<?php echo $ac['logo_path']; ?>" style="height: 40px; object-fit: contain; margin-bottom: 5px;">
                                <p style="font-size: 0.7rem; font-weight: 600;"><?php echo $ac['name']; ?></p>
                                <a href="?delete_accred=<?php echo $ac['id']; ?>" style="color: var(--danger); font-size: 0.7rem;">Remove</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- About Us Content Tab -->
            <div id="tab-about" class="tab-content" style="display: none;">
                <div class="form-group">
                    <label>Main About Content</label>
                    <textarea name="about_text" class="form-control" rows="6"><?php echo $institute['about_text'] ?? ''; ?></textarea>
                </div>
                
                <div style="margin-top: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label>History Title</label>
                        <input type="text" name="history_title" class="form-control" value="<?php echo $institute['history_title'] ?? 'Our Legacy'; ?>">
                    </div>
                    <div class="form-group">
                        <label>History Text</label>
                        <textarea name="history_text" class="form-control" rows="4"><?php echo $institute['history_text'] ?? ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Vision Statement</label>
                        <textarea name="vision" class="form-control" rows="4"><?php echo $institute['vision'] ?? ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Mission Statement</label>
                        <textarea name="mission" class="form-control" rows="4"><?php echo $institute['mission'] ?? ''; ?></textarea>
                    </div>
                </div>

                <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem;">Leadership Messages</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div style="border: 1px solid var(--border); padding: 1.5rem; border-radius: 12px;">
                            <h4 style="font-size: 0.875rem; margin-bottom: 1rem;">Principal's Message</h4>
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="principal_name" class="form-control" value="<?php echo $institute['principal_name'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea name="principal_message" class="form-control" rows="4"><?php echo $institute['principal_message'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Photo</label>
                                <input type="file" name="principal_photo" class="form-control">
                                <?php if (!empty($institute['principal_photo'])): ?>
                                    <img src="../<?php echo $institute['principal_photo']; ?>" style="height: 60px; border-radius: 8px; margin-top: 10px;">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="border: 1px solid var(--border); padding: 1.5rem; border-radius: 12px;">
                            <h4 style="font-size: 0.875rem; margin-bottom: 1rem;">Chairman's Message</h4>
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="chairman_name" class="form-control" value="<?php echo $institute['chairman_name'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea name="chairman_message" class="form-control" rows="4"><?php echo $institute['chairman_message'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Photo</label>
                                <input type="file" name="chairman_photo" class="form-control">
                                <?php if (!empty($institute['chairman_photo'])): ?>
                                    <img src="../<?php echo $institute['chairman_photo']; ?>" style="height: 60px; border-radius: 8px; margin-top: 10px;">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>Organizational Chart (Image)</label>
                            <input type="file" name="org_chart_image" class="form-control">
                            <?php if (!empty($institute['org_chart_image'])): ?>
                                <img src="../<?php echo $institute['org_chart_image']; ?>" style="max-width: 100%; border-radius: 8px; margin-top: 10px; border: 1px solid var(--border);">
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Virtual Tour Embed (Iframe Code)</label>
                            <textarea name="virtual_tour_embed" class="form-control" rows="6" placeholder="Paste iframe code here"><?php echo $institute['virtual_tour_embed'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sliders Tab -->
            <div id="tab-sliders" class="tab-content" style="display: none;">
                <h3 style="font-size: 1rem; margin-bottom: 1rem;">Homepage Banner Sliders</h3>
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">Upload up to 10 images. Ideal size: 1920x600px.</p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <input type="file" name="slider_image" class="form-control">
                        <input type="text" name="slider_title" class="form-control" placeholder="Caption Title">
                    </div>
                </div>

                <div class="slider-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                    <?php
                    $sliders = $pdo->query("SELECT * FROM home_sliders ORDER BY sort_order ASC")->fetchAll();
                    foreach ($sliders as $slide):
                    ?>
                    <div class="card" style="padding: 0.5rem; position: relative;">
                        <img src="../<?php echo $slide['image_path']; ?>" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px;">
                        <p style="font-size: 0.75rem; margin-top: 5px; font-weight: 600;"><?php echo $slide['caption_title']; ?></p>
                        <a href="institute.php?delete_slide=<?php echo $slide['id']; ?>" style="position: absolute; top: 10px; right: 10px; background: rgba(239, 68, 68, 0.8); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                            <i data-lucide="x" size="14"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Social Tab -->
            <div id="tab-social" class="tab-content" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label><i data-lucide="facebook"></i> Facebook URL</label>
                        <input type="url" name="facebook_url" class="url-control" value="<?php echo $institute['facebook_url'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label><i data-lucide="instagram"></i> Instagram URL</label>
                        <input type="url" name="instagram_url" class="url-control" value="<?php echo $institute['instagram_url'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label><i data-lucide="linkedin"></i> LinkedIn URL</label>
                        <input type="url" name="linkedin_url" class="url-control" value="<?php echo $institute['linkedin_url'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label><i data-lucide="twitter"></i> Twitter URL</label>
                        <input type="url" name="twitter_url" class="url-control" value="<?php echo $institute['twitter_url'] ?? ''; ?>">
                    </div>
                </div>
            </div>

            <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2.5rem; font-size: 1rem;">
                    Save All Changes
                </button>
            </div>
        </div>
    </form>
</div>

<style>
    .tab-btn {
        background: transparent;
        border: none;
        padding: 1rem 0;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        position: relative;
    }
    .tab-btn.active {
        color: var(--accent);
    }
    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 2px;
        background: var(--accent);
    }
    .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--text-main);
    }
    .form-control, .url-control {
        height: 52px;
        padding: 0 1.25rem;
        font-size: 1rem;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: #fcfdfe;
        transition: all 0.2s ease;
        width: 100%;
    }
    .form-control:focus, .url-control:focus {
        border-color: var(--accent);
        background: white;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        outline: none;
    }
    textarea.form-control {
        height: auto;
        padding: 1.25rem;
    }
    .btn-primary {
        padding: 1rem 3rem;
        font-size: 1rem;
        font-weight: 700;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
</style>

<script>
    function showTab(tabId) {
        // Hide all contents
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        // Deactivate all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        
        // Show target content
        document.getElementById('tab-' + tabId).style.display = 'block';
        // Activate target button
        event.currentTarget.classList.add('active');
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 3 seconds
        setTimeout(function() {
            const successAlert = document.getElementById('success-alert');
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s';
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500);
            }
            
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url);
        }, 3000);
    });
</script>

<?php include '../includes/footer.php'; ?>

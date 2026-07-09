<main class="auth-shell">
    <section class="auth-card wide">
        <a class="brand centered" href="<?= h(public_url('')) ?>">
            <img class="brand-logo" src="<?= h(public_url('assets/img/logo.png')) ?>" alt="لوگوی گپ‌هوش">
            <span>گپ‌هوش</span>
        </a>
        <h1>ثبت‌نام در گپ‌هوش</h1>
        <p>شماره موبایل اجباری است. ایمیل اختیاری است و می‌توانی خالی بگذاری.</p>

        <?php if (!empty($error)): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>

        <form method="post" action="<?= h(public_url('register')) ?>" class="form-card grid-form">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <label>نام و نام خانوادگی
                <input name="name" autocomplete="name" required placeholder="مثلاً شایان" value="<?= h($old['name'] ?? '') ?>">
            </label>
            <label>شماره موبایل
                <input name="mobile" inputmode="tel" autocomplete="tel" required placeholder="09123456789" value="<?= h($old['mobile'] ?? '') ?>">
            </label>
            <label>ایمیل <span class="optional">اختیاری</span>
                <input name="email" type="email" autocomplete="email" placeholder="you@example.com" value="<?= h($old['email'] ?? '') ?>">
            </label>
            <label>رمز عبور
                <input name="password" type="password" autocomplete="new-password" required placeholder="حداقل ۸ کاراکتر">
            </label>
            <button class="btn primary full span-2" type="submit">ساخت حساب</button>
        </form>
        <p class="switch-link">قبلاً ثبت‌نام کردی؟ <a href="<?= h(public_url('login')) ?>">وارد شو</a></p>
    </section>
</main>

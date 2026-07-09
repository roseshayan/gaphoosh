<main class="auth-shell">
    <section class="auth-card">
        <a class="brand centered" href="<?= h(public_url('')) ?>">
            <img class="brand-logo" src="<?= h(public_url('assets/img/logo.png')) ?>" alt="لوگوی گپ‌هوش">
            <span>گپ‌هوش</span>
        </a>
        <h1>ورود به حساب</h1>
        <p>برای ادامه گفتگوهای قبلی، شماره موبایل و رمز عبور را وارد کن.</p>

        <?php if (!empty($error)): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>

        <form method="post" action="<?= h(public_url('login')) ?>" class="form-card">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <label>شماره موبایل
                <input name="mobile" inputmode="tel" autocomplete="tel" required placeholder="09123456789" value="<?= h($old['mobile'] ?? '') ?>">
            </label>
            <label>رمز عبور
                <input name="password" type="password" autocomplete="current-password" required placeholder="حداقل ۸ کاراکتر">
            </label>
            <button class="btn primary full" type="submit">ورود</button>
        </form>
        <p class="switch-link">حساب نداری؟ <a href="<?= h(public_url('register')) ?>">ثبت‌نام کن</a></p>
    </section>
</main>

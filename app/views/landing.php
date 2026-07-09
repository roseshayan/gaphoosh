<header class="site-header landing-header">
    <a class="brand" href="<?= h(public_url('')) ?>" aria-label="گپ‌هوش">
        <img class="brand-logo" src="<?= h(public_url('assets/img/logo.png')) ?>" alt="لوگوی گپ‌هوش" loading="eager">
        <span>گپ‌هوش</span>
    </a>
    <nav class="top-nav" aria-label="منوی اصلی">
        <a href="#usecases">کاربردها</a>
        <a href="#features">امکانات</a>
        <a href="#security">امنیت</a>
        <a href="<?= h(public_url('login')) ?>">ورود</a>
        <a class="btn small primary" href="<?= h(public_url('register')) ?>">شروع رایگان</a>
    </nav>
</header>

<main>
    <section class="hero landing-hero">
        <div class="hero-content">
            <p class="eyebrow">هوش مصنوعی فارسی، ساده و در دسترس</p>
            <h1>با گپ‌هوش، با چند مدل هوش مصنوعی فارسی گفتگو کن.</h1>
            <p class="hero-text">سؤال بپرس، متن بنویس، کد بگیر، ایده بساز و گفتگوهای قبلی‌ات را ادامه بده. گپ‌هوش برای کاربر فارسی‌زبان طراحی شده؛ راست‌چین، سریع، خلوت و بدون پیچیدگی اضافه.</p>
            <div class="hero-actions">
                <a class="btn primary large" href="<?= h(public_url('register')) ?>">شروع گفتگو رایگان</a>
                <a class="btn ghost large" href="#preview">دیدن نمونه محیط</a>
            </div>
            <div class="trust-row" aria-label="مزیت‌های اصلی">
                <span>استریم لحظه‌ای</span>
                <span>ذخیره چت‌ها</span>
                <span>مناسب کدنویسی</span>
                <span>کاملاً راست‌چین</span>
            </div>
        </div>
        <div class="hero-product" id="preview" aria-label="نمونه محیط چت گپ‌هوش">
            <div class="product-window">
                <div class="window-bar"><span></span><span></span><span></span><strong>gaphoosh.ir</strong></div>
                <div class="product-chat">
                    <div class="product-msg user">یک تابع PHP امن برای اعتبارسنجی موبایل ایران بنویس.</div>
                    <div class="product-msg ai">
                        <p>این نمونه ساده، خوانا و قابل استفاده است:</p>
                        <div class="code-preview" dir="ltr">
                            <div class="code-preview-head"><span>PHP</span><i></i></div>
<pre><code>function isValidIranMobile(string $mobile): bool {
    $mobile = preg_replace('/\D+/', '', $mobile);
    return preg_match('/^09\d{9}$/', $mobile) === 1;
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="usecases" class="section usecases">
        <div class="section-title centered-title">
            <p class="eyebrow">برای چه کاری؟</p>
            <h2>یک ابزار سبک برای کارهای واقعی روزمره.</h2>
        </div>
        <div class="usecase-grid">
            <article><span>01</span><h3>کدنویسی و دیباگ</h3><p>کد را تمیزتر بگیر، خطاها را بفهم و خروجی را در بلاک‌های کد خوانا ببین.</p></article>
            <article><span>02</span><h3>تولید محتوا</h3><p>متن سایت، کپشن، عنوان، ایده بلاگ و توضیح محصول را سریع‌تر آماده کن.</p></article>
            <article><span>03</span><h3>یادگیری</h3><p>مفاهیم سخت را با زبان ساده و مثال‌های قابل فهم بپرس.</p></article>
        </div>
    </section>

    <section id="features" class="section compact">
        <div class="section-title centered-title">
            <p class="eyebrow">امکانات کلیدی</p>
            <h2>چیزی که برای یک چت AI درست لازم است، نه یک دمو نصفه.</h2>
        </div>
        <div class="feature-grid refined">
            <article><div class="feature-icon">↯</div><h3>پاسخ کلمه‌به‌کلمه</h3><p>پاسخ‌ها با SSE نمایش داده می‌شوند تا کاربر منتظر پایان کامل خروجی نماند.</p></article>
            <article><div class="feature-icon">☰</div><h3>مدیریت گفتگو</h3><p>گفتگوها و پیام‌ها در MySQL ذخیره می‌شوند و کاربر می‌تواند ادامه بدهد.</p></article>
            <article><div class="feature-icon">◇</div><h3>چند مدل AI</h3><p>مدل‌ها از endpoint زنده Dahl خوانده می‌شوند و فقط به یک مدل قفل نیستی.</p></article>
            <article><div class="feature-icon">{ }</div><h3>نمایش تمیز کد</h3><p>کدها چپ‌چین، خوانا، قابل کپی و جدا از متن فارسی رندر می‌شوند.</p></article>
        </div>
    </section>

    <section id="security" class="section split premium-split">
        <div>
            <p class="eyebrow">امنیت از روز اول</p>
            <h2>کلید هوش مصنوعی سمت مرورگر نمی‌رود.</h2>
            <p>درخواست‌ها از بک‌اند PHP عبور می‌کنند؛ کلید Dahl داخل `.env` می‌ماند، فرم‌ها CSRF دارند، رمزها hash می‌شوند و دیتابیس با prepared statement کار می‌کند.</p>
        </div>
        <div class="security-card">
            <ul class="clean-list">
                <li>ثبت‌نام با موبایل اجباری</li>
                <li>ایمیل اختیاری</li>
                <li>پنل مدیریت کاربران و مصرف</li>
                <li>بدون CDN و asset خارجی</li>
            </ul>
        </div>
    </section>

    <section class="cta-band premium-cta">
        <img src="<?= h(public_url('assets/img/logo.png')) ?>" alt="گپ‌هوش" loading="lazy">
        <h2>اولین گفتگو را شروع کن.</h2>
        <p>گپ‌هوش برای فارسی‌زبان‌ها ساخته شده؛ سبک، سریع و مناسب تولید محتوا، یادگیری و کدنویسی.</p>
        <a class="btn primary large" href="<?= h(public_url('register')) ?>">ساخت حساب رایگان</a>
    </section>
</main>

<footer class="site-footer">
    <span>© <?= date('Y') ?> گپ‌هوش</span>
    <span>هوش مصنوعی فارسی در gaphoosh.ir</span>
</footer>

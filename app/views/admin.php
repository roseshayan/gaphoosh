<div class="admin-app" data-base-url="<?= h(public_url('')) ?>">
    <header class="admin-header">
        <a class="brand" href="<?= h(public_url('chat')) ?>">
            <img class="brand-logo" src="<?= h(public_url('assets/img/logo.png')) ?>" alt="لوگوی گپ‌هوش">
            <span>پنل مدیریت گپ‌هوش</span>
        </a>
        <nav class="admin-nav">
            <a class="btn ghost small" href="<?= h(public_url('chat')) ?>">بازگشت به چت</a>
            <form method="post" action="<?= h(public_url('logout')) ?>">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <button class="btn primary small" type="submit">خروج</button>
            </form>
        </nav>
    </header>

    <main class="admin-main">
        <section class="admin-hero-card">
            <div>
                <p class="eyebrow">داشبورد مدیریت</p>
                <h1>کاربران، گفتگوها و خطاهای API را کنترل کن.</h1>
                <p id="adminStatus">در حال بارگذاری اطلاعات…</p>
            </div>
            <button class="btn primary" id="refreshAdmin" type="button">به‌روزرسانی</button>
        </section>

        <section class="stat-grid" id="statGrid"></section>

        <section class="admin-panel dahl-diagnostic-panel">
            <div class="panel-head">
                <div>
                    <h2>عیب‌یابی اتصال Dahl</h2>
                    <p>مدل‌ها، ساخت توکن inference و تست یک پیام کوتاه را بدون نمایش کلید بررسی کن.</p>
                </div>
                <button class="btn ghost small" id="runDahlDiagnostic" type="button">بررسی اتصال</button>
            </div>
            <pre class="diagnostic-box" id="dahlDiagnosticBox">برای بررسی خطای ۴۰۳ روی دکمه بالا بزن.</pre>
        </section>

        <section class="admin-panel">
            <div class="panel-head"><h2>کاربران</h2><p>مسدودسازی، فعال‌سازی و دسترسی ادمین</p></div>
            <div class="table-wrap"><table id="usersTable"><thead><tr><th>کاربر</th><th>موبایل</th><th>وضعیت</th><th>ادمین</th><th>گفتگو</th><th>پیام</th><th>عملیات</th></tr></thead><tbody></tbody></table></div>
        </section>

        <section class="admin-grid-two">
            <div class="admin-panel">
                <div class="panel-head"><h2>گفتگوهای اخیر</h2><p>برای حذف نرم گفتگوهای مشکل‌دار</p></div>
                <div class="table-wrap"><table id="conversationsTable"><thead><tr><th>عنوان</th><th>کاربر</th><th>مدل</th><th>پیام</th><th>عملیات</th></tr></thead><tbody></tbody></table></div>
            </div>
            <div class="admin-panel">
                <div class="panel-head"><h2>لاگ Dahl</h2><p>خطاهای ۴۰۳/۵۰۲ و مصرف توکن</p></div>
                <div class="table-wrap"><table id="logsTable"><thead><tr><th>زمان</th><th>کاربر</th><th>مدل</th><th>وضعیت</th><th>خطا</th></tr></thead><tbody></tbody></table></div>
            </div>
        </section>

        <section class="admin-grid-two">
            <div class="admin-panel">
                <div class="panel-head"><h2>لاگ امنیتی</h2><p>ورود ناموفق، OTP، rate limit و سوءاستفاده</p></div>
                <div class="table-wrap"><table id="securityLogsTable"><thead><tr><th>زمان</th><th>رخداد</th><th>موبایل/IP</th><th>جزئیات</th></tr></thead><tbody></tbody></table></div>
            </div>
            <div class="admin-panel">
                <div class="panel-head"><h2>Backup و مصرف روزانه</h2><p>آخرین backupها و مانیتورینگ مصرف توکن</p></div>
                <div class="table-wrap"><table id="backupLogsTable"><thead><tr><th>زمان</th><th>وضعیت</th><th>فایل</th><th>حجم</th></tr></thead><tbody></tbody></table></div>
                <div class="table-wrap mini-table"><table id="tokenDailyTable"><thead><tr><th>روز</th><th>درخواست</th><th>توکن</th><th>خطا</th></tr></thead><tbody></tbody></table></div>
            </div>
        </section>
    </main>
</div>

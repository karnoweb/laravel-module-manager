# سناریوهای کاربردی Laravel Module Manager

این سند به‌زبان فارسی توضیح می‌دهد در چه موقعیت‌هایی و چطور از پکیج **Laravel Module Manager** استفاده کنید. برای مرجع کامل API و دستورات Artisan به [README اصلی](../../README.md) مراجعه کنید.

---

## ۱. مقدمه

### این پکیج چه مشکلی را حل می‌کند؟

- **ماژول‌های فعال/غیرفعال:** امکان روشن و خاموش کردن قابلیت‌ها (feature) بدون حذف کد؛ مناسب برای فروشگاه، حسابداری، CRM، HRM و اپ‌های چندماژوله.
- **وابستگی (dependency):** اطمینان از اینکه ماژولی فقط وقتی فعال شود که ماژول‌های موردنیازش فعال باشند؛ و جلوگیری از غیرفعال کردن ماژولی که دیگران به آن وابسته‌اند (در حالت restrict).
- **درخت (tree):** گروه‌بندی و نمایش سلسله‌مراتبی ماژول‌ها (مثلاً محصولات → محصول ساده / محصول متغیر) برای منو و پنل ادمین.

### چه زمانی از این پکیج استفاده کنیم؟

- اپلیکیشن چندماژوله یا چندفیچر (فروشگاه، حسابداری، CRM، HRM).
- نیاز به فعال/غیرفعال کردن قابلیت‌ها از config یا پنل، بدون deploy مجدد.
- نیاز به وابستگی منطقی بین ماژول‌ها (requires، conflicts، suggests) و درخت نمایش (parent/children).

---

## ۲. مفاهیم پایه

### تفاوت parent/children با requires/conflicts

| مفهوم | کاربرد |
|--------|--------|
| **parent / children** | گروه‌بندی و درخت **نمایش** (منو، دسته‌بندی در پنل). مثلاً «محصولات» والد «محصول ساده» و «محصول متغیر» است. |
| **requires / conflicts / suggests** | وابستگی **منطقی** در زمان اجرا. مثلاً ماژول «محصول ساده» به ماژول «محصولات» وابسته است؛ اگر «محصولات» غیرفعال باشد، «محصول ساده» قابل فعال‌سازی نیست. |

یعنی: درخت برای **ظاهر و دسته‌بندی** است؛ وابستگی‌ها برای **قوانین فعال/غیرفعال** هستند.

### فعال / غیرفعال و ماژول سیستمی (قفل‌شده)

- **فعال (active):** ماژول روشن است؛ روت‌ها، منوها و منطق مربوط به آن در دسترس است.
- **غیرفعال (inactive):** ماژول خاموش است؛ معمولاً از Blade و middleware با `@module` و `module:...` جلوی دسترسی گرفته می‌شود.
- **ماژول سیستمی (system / قفل‌شده):** با `is_system => true` تعریف می‌شود؛ با `Module::deactivate()` یا دستور `module:deactivate` **نمی‌توان** آن را غیرفعال کرد (مثل هسته حسابداری یا core).

### رفتارهای غیرفعال‌سازی (on_deactivate)

| رفتار | معنی | چه موقع استفاده کنیم |
|--------|------|------------------------|
| **restrict** | اگر ماژول دیگری به این وابسته باشد، غیرفعال کردن **ممنوع** است. | وقتی نمی‌خواهیم وضعیت ناسازگار پیش بیاید (مثلاً حسابداری که گزارش‌ها به آن وابسته‌اند). |
| **cascade** | با غیرفعال کردن این ماژول، ماژول‌های وابسته (dependents) هم **خودکار** غیرفعال می‌شوند. | وقتی ماژول «والد» را خاموش می‌کنیم و می‌خواهیم زیرماژول‌ها هم خاموش شوند (مثلاً ماژول فروشگاه). |
| **none** | حتی با وجود وابسته‌های فعال هم می‌توان این ماژول را غیرفعال کرد؛ وابسته‌ها دست‌نخورده می‌مانند. | فقط در سناریوهای خاص که آگاهانه وضعیت ناسازگار را می‌پذیریم. |

---

## ۳. سناریو: ماژول‌های تو در تو فروشگاه

### درخت و وابستگی‌ها

```
فروشگاه (shop)
├── محصولات (products)
│   ├── محصول ساده (simple_product)   → requires: products
│   └── محصول متغیر (variable_product) → requires: products
├── سبد خرید (cart)                    → requires: products
├── تخفیف ساده (simple_discount)       → requires: products
└── تخفیف پیشرفته (advanced_discount)  → requires: products, simple_discount
```

- **parent/children:** برای منوی فروشگاه و دسته‌بندی در پنل (مثلاً «محصولات» والد «محصول ساده» و «محصول متغیر»).
- **requires:** صفحه checkout به سبد و پرداخت وابسته است؛ «تخفیف پیشرفته» به «محصولات» و «تخفیف ساده» وابسته است.

### نمونه تعریف در config (ساختار تو در تو با `records`)

با استفاده از کلید **records** نیازی نیست برای هر زیرماژول دوباره `group` و `parent` بنویسید؛ گروه و والد به‌صورت خودکار از ماژول والد به‌ارث می‌روند.

```php
// config/module-manager.php
'modules' => [
    'products' => [
        'name' => 'محصولات',
        'description' => 'مدیریت محصولات',
        'group' => 'shop',
        'icon' => 'fa-box',
        'sort_order' => 0,
        'is_active' => false,
        'is_system' => false,
        'on_deactivate' => 'cascade',
        'requires' => [],
        'conflicts' => [],
        'suggests' => [],
        'records' => [
            'simple_product' => [
                'name' => 'محصول ساده',
                'is_active' => false,
                'requires' => ['products'],
            ],
            'variable_product' => [
                'name' => 'محصول متغیر',
                'requires' => ['products'],
            ],
        ],
    ],
    'cart' => [
        'name' => 'سبد خرید',
        'group' => 'shop',
        'requires' => ['products'],
    ],
    'advanced_discount' => [
        'name' => 'تخفیف پیشرفته',
        'group' => 'shop',
        'requires' => ['products', 'simple_discount'],
    ],
],
```

بعد از ویرایش، دستور `php artisan module:sync` را اجرا کنید تا ماژول‌ها در دیتابیس ساخته/به‌روز شوند.

### استفاده در Blade و Route

- **Blade:** فقط وقتی ماژول فروشگاه (مثلاً `products`) فعال است، لینک منو نمایش داده شود:

```blade
@module('products')
    <a href="{{ route('products.index') }}">محصولات</a>
@endmodule

@moduleany(['simple_product', 'variable_product'])
    <a href="{{ route('products.index') }}">محصولات</a>
@endmoduleany
```

- **Route:** فقط در صورت فعال بودن ماژول، روت در دسترس باشد:

```php
Route::middleware(['module:products'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
});

Route::middleware(['module:products,cart'])->get('/checkout', [CheckoutController::class, 'index']);
```

---

## ۴. سناریو: حسابداری

### ماژول‌های پیشنهادی

- **core_accounting (هسته حسابداری):** ماژول پایه؛ بهتر است `is_system => true` باشد تا غیرفعال نشود.
- **reports (گزارش‌ها):** وابسته به هسته حسابداری.
- **multi_currency (چند ارزه):** اختیاری؛ وابسته به هسته.
- **invoicing (صدور فاکتور):** در صورت یکپارچگی با فروشگاه، به ماژول فروشگاه هم وابسته باشد.

### وابستگی و ماژول سیستمی

- گزارش‌ها و چند ارزه و فاکتور **requires** به `core_accounting`.
- برای هسته حسابداری از **restrict** یا **سیستمی** استفاده کنید تا با غیرفعال کردن تصادفی، ماژول‌های وابسته خراب نشوند.
- وقتی ماژول‌های دیگر (مثلاً فروشگاه) به حسابداری وابسته‌اند، برای حسابداری `on_deactivate => 'restrict'` مناسب است تا تا زمانی که وابسته‌ها فعال‌اند، نتوان حسابداری را خاموش کرد.

### نمونه خلاصه config

```php
'core_accounting' => [
    'name' => 'هسته حسابداری',
    'group' => 'accounting',
    'is_active' => true,
    'is_system' => true,
    'on_deactivate' => 'restrict',
    'requires' => [],
],
'reports' => [
    'name' => 'گزارش‌های حسابداری',
    'group' => 'accounting',
    'requires' => ['core_accounting'],
],
'invoicing' => [
    'name' => 'صدور فاکتور',
    'group' => 'accounting',
    'requires' => ['core_accounting'], // در صورت یکپارچگی: ['core_accounting', 'products']
],
```

---

## ۵. سناریو: CRM

### ماژول‌های پیشنهادی

- مخاطبین (contacts)، pipeline فروش (pipeline)، بازاریابی (marketing)، تیکتینگ (ticketing).
- گزارش‌های CRM می‌تواند به ماژول گزارش (reports) **suggests** داشته باشد؛ یعنی پیشنهاد می‌شود گزارش فعال باشد، ولی اجباری نیست.

### تعارض (conflicts)

- اگر نسخه قدیم و جدید CRM دارید، با **conflicts** جلوی فعال بودن همزمان را بگیرید:

```php
Module::conflicts('crm_legacy', 'crm_v2');
```

در config می‌توانید برای هر ماژول آرایه `conflicts` تعریف کنید و بعد با `module:sync` همگام کنید.

### نمونه وابستگی

- **pipeline** و **marketing** و **ticketing** هر کدام به **contacts** وابسته (requires) باشند.
- **crm_reports** به **contacts** requires و به **reports** suggests داشته باشد.

---

## ۶. سناریو: HRM

### ماژول‌ها و درخت نمایش

- کارمندان (employees)، حضور و غیاب (attendance)، حقوق (payroll)، استخدام (recruitment).
- از **parent/children** برای منوی HR استفاده کنید؛ مثلاً «کارمندان» والد «حضور و غیاب» و «حقوق» برای نمایش دسته‌بندی در پنل.

### وابستگی منطقی

- **payroll** به **attendance** و **employees** وابسته (requires) باشد.
- **recruitment** می‌تواند فقط به **employees** وابسته باشد.

### رفتار cascade

- اگر ماژول اصلی HR (مثلاً `hrm_core`) را غیرفعال می‌کنید و می‌خواهید همه زیرماژول‌ها (حضور، حقوق، استخدام) هم غیرفعال شوند، برای آن ماژول `on_deactivate => 'cascade'` قرار دهید و وابستگی‌ها (requires) را درست تعریف کنید تا رزولر وابسته‌ها را پیدا کرده و به‌ترتیب غیرفعال کند.

---

## ۷. نمونه کامل لیست ماژول (سیستم پیشرفته فروشگاه + حسابداری)

این بخش یک **نمونه اولیه و کامل** از تعریف ماژول‌ها برای یک سیستم واقعی را نشان می‌دهد: فروشگاه (محصولات، تخفیف، کیف پول، فروش عمده، گزارش فروش) و حسابداری (تعریف حساب، بانک، صندوق، پوز، دریافت/پرداخت/حواله، گزارش تخفیفات و گزارش فروش). همه حالت‌های منطقی (parent/records، requires، is_system، on_deactivate) پشتیبانی شده‌اند.

### منطق وابستگی‌ها (خلاصه)

| حوزه | ماژول پایه | وابستگیهای مهم |
|------|-------------|------------------|
| **فروشگاه** | محصولات (products) | محصول ساده/متغیر → products؛ فروش عمده، سبد، گزارش فروش → products؛ تخفیف پیشرفته → تخفیف ساده؛ کیف پول می‌تواند مستقل یا وابسته به محصولات باشد. |
| **حسابداری** | تعریف حساب (account_definition) | بانک، صندوق، پوز، دریافت‌/پرداخت‌/حواله → account_definition؛ پوز → بانک؛ گزارش تخفیفات → discount (فروشگاه)؛ گزارش فروش حسابداری → sales_report (فروشگاه). |

- **ماژول سیستمی:** فقط `account_definition` (هسته تعریف حساب) تا با غیرفعال شدن تصادفی، کل حسابداری خراب نشود.
- **on_deactivate:** برای والدهای درختی (مثل products، discount) از `cascade`؛ برای account_definition از `restrict`.

### نمونه config کامل (ساختار تو در تو)

```php
'modules' => [
    // ─────────── فروشگاه ───────────
    'products' => [
        'name' => 'محصولات',
        'description' => 'مدیریت محصولات و انواع آن',
        'group' => 'shop',
        'icon' => 'fa-box',
        'sort_order' => 10,
        'is_active' => false,
        'is_system' => false,
        'on_deactivate' => 'cascade',
        'requires' => [],
        'records' => [
            'simple_product' => [
                'name' => 'محصول ساده',
                'description' => 'محصول با یک قیمت و موجودی واحد',
                'requires' => ['products'],
            ],
            'variable_product' => [
                'name' => 'محصول متغیر',
                'description' => 'محصول با چند نوع (رنگ، سایز و...)',
                'requires' => ['products'],
            ],
        ],
    ],
    'sales_units' => [
        'name' => 'واحدهای فروش مجزا',
        'description' => 'تعریف واحدهای فروش (عدد، کارتن، کیلو و...)',
        'group' => 'shop',
        'requires' => ['products'],
    ],
    'wallet' => [
        'name' => 'کیف پول',
        'description' => 'کیف پول و اعتبار مشتری',
        'group' => 'shop',
        'requires' => [], // مستقل؛ در صورت نیاز: ['products']
    ],
    'wholesale' => [
        'name' => 'فروش عمده',
        'description' => 'قیمت و حداقل تعداد برای فروش عمده',
        'group' => 'shop',
        'requires' => ['products'],
    ],
    'discount' => [
        'name' => 'تخفیفات',
        'description' => 'سیستم تخفیف روی محصولات و فاکتور',
        'group' => 'shop',
        'on_deactivate' => 'cascade',
        'requires' => ['products'],
        'records' => [
            'simple_discount' => [
                'name' => 'تخفیف ساده',
                'requires' => ['discount'],
            ],
            'advanced_discount' => [
                'name' => 'تخفیف پیشرفته',
                'description' => 'قوانین ترکیبی و کوپن',
                'requires' => ['discount', 'simple_discount'],
            ],
        ],
    ],
    'sales_report' => [
        'name' => 'گزارش فروش',
        'description' => 'گزارش فروش و عملکرد فروشگاه',
        'group' => 'shop',
        'requires' => ['products'],
    ],
    'cart' => [
        'name' => 'سبد خرید',
        'group' => 'shop',
        'requires' => ['products'],
    ],

    // ─────────── حسابداری ───────────
    'account_definition' => [
        'name' => 'تعریف حساب',
        'description' => 'امکان تعریف حساب‌های بانکی، صندوق و تفصیلی',
        'group' => 'accounting',
        'icon' => 'fa-calculator',
        'sort_order' => 0,
        'is_active' => false,
        'is_system' => true,
        'on_deactivate' => 'restrict',
        'requires' => [],
    ],
    'bank' => [
        'name' => 'بانک',
        'description' => 'حساب‌های بانکی و عملیات بانکی',
        'group' => 'accounting',
        'requires' => ['account_definition'],
    ],
    'cashbox' => [
        'name' => 'صندوق',
        'description' => 'صندوق نقدی و تنخواه',
        'group' => 'accounting',
        'requires' => ['account_definition'],
    ],
    'pos' => [
        'name' => 'پوز',
        'description' => 'درگاه و دستگاه پوز (وابسته به بانک)',
        'group' => 'accounting',
        'requires' => ['bank'],
    ],
    'receipt_payment_transfer' => [
        'name' => 'دریافت، پرداخت، حواله',
        'description' => 'ثبت دریافت، پرداخت و حواله بین حساب‌ها',
        'group' => 'accounting',
        'requires' => ['account_definition'],
    ],
    'discount_report' => [
        'name' => 'گزارش تخفیفات',
        'description' => 'گزارش تخفیفات (فقط وقتی تخفیفات فروشگاه فعال است)',
        'group' => 'accounting',
        'requires' => ['account_definition', 'discount'],
    ],
    'sales_report_accounting' => [
        'name' => 'گزارش فروش (حسابداری)',
        'description' => 'انطباق گزارش فروش با حسابداری',
        'group' => 'accounting',
        'requires' => ['account_definition', 'sales_report'],
    ],
],
```

### نکات برای استفاده در پروژه واقعی

1. **ترتیب فعال‌سازی:** ابتدا `products` و در صورت نیاز `discount` و `sales_report` را فعال کنید؛ بعد در حسابداری ابتدا `account_definition`، سپس `bank` / `cashbox`، بعد `pos` (در صورت نیاز) و در نهایت ماژول‌های گزارش.
2. **گزارش تخفیفات:** ماژول `discount_report` فقط وقتی قابل فعال‌سازی است که ماژول `discount` (فروشگاه) فعال باشد؛ در کد می‌توانید با `Module::when('discount_report', ...)` فقط در صورت فعال بودن، منو یا روت گزارش تخفیفات را نشان دهید.
3. **گزارش فروش حسابداری:** وابسته به `sales_report` است تا دادهٔ فروش از فروشگاه در گزارش حسابداری استفاده شود.
4. **پوز:** عمداً فقط به `bank` وابسته است؛ صندوق و بانک هر دو به `account_definition` وابسته‌اند تا بدون تعریف حساب، عملیات بانک/صندوق فعال نشود.

بعد از کپی این نمونه در `config/module-manager.php` و تطبیق با نیاز پروژه، دستور `php artisan module:sync` را اجرا کنید.

---

## ۸. جمع‌بندی و چک‌لیست

### پیشنهاد نوع وابستگی و رفتار به‌ازای هر حوزه

| حوزه | وابستگی معمول | on_deactivate پیشنهادی | ماژول سیستمی |
|------|----------------|-------------------------|----------------|
| فروشگاه | محصول ساده/متغیر → products؛ تخفیف پیشرفته → products + تخفیف ساده | cascade برای parents مثل products | معمولاً خیر |
| حسابداری | گزارش/فاکتور/چند ارزه → core_accounting | restrict برای core؛ پیش‌فرض برای بقیه | بله برای هسته (core_accounting) |
| CRM | pipeline/marketing/ticketing → contacts | restrict یا cascade بسته به طراحی | در صورت داشتن core بله |
| HRM | payroll → attendance + employees | cascade برای ماژول اصلی HR در صورت تمایل | در صورت داشتن core بله |

### لینک به مستندات اصلی

- **API و متدهای Facade و Helper:** [README — API Reference](../../README.md#api-reference)
- **دستورات Artisan:** [README — Artisan commands](../../README.md#artisan-commands)
- **نصب و پیکربندی:** [README — Installation & Configuration](../../README.md#installation)

اگر سوالی درباره سناریوی خاصی دارید، می‌توانید از متدهای `Module::whyCantActivate()` و `Module::whyCantDeactivate()` برای تشخیص دلیل مسدود شدن فعال/غیرفعال‌سازی استفاده کنید.

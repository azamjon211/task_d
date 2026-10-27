# Yetkazib berish shartnomasi: jarima va buxgalteriya

Laravel 12 + Blade. Shartnoma yuritish, kechikish jarimasi (boshliq tasdig'i bilan) va buxgalteriya:
to'lov bosqichlari, smeta (byudjet) qoldig'i, to'lov reestri va bekor qilish.

## Ishga tushirish

Talablar: PHP 8.3+ (`bcmath`, `pdo_pgsql`), PostgreSQL, Composer. Front-end yig'ish (npm) shart emas.

```bash
composer install
cp .env.example .env && php artisan key:generate   # .env da DB_USERNAME / DB_PASSWORD ni tekshiring
createdb -U postgres task && createdb -U postgres task_test
php artisan migrate --seed
php artisan serve                                   # http://localhost:8000
```

## Demo foydalanuvchilar

| Rol       | Email               | Parol      |
|-----------|---------------------|------------|
| Ijrochi   | ijrochi@demo.uz     | `password` |
| Boshliq   | boshliq@demo.uz     | `password` |
| Buxgalter | buxgalter@demo.uz   | `password` |

Seeder joriy yil uchun quyidagilarni yaratadi:

- smeta: "Dori vositalari" — mart 3000,00, aprel 2000,00;
- `SH-<yil>/001` — faol, hech narsa kelmagan (1-misol: hisoblash sanasi 15-mart → jarima 37,50);
- `SH-<yil>/002` — faol, 14-martda 600 dona kelgan (2-misol: hisoblash sanasi 16-mart → jarima 35,00);
- `SH-<yil>/003` — qoralama (buxgalteriya A misoli).

Barcha shartnomalar: 1000 dona × 2,50 + 50 bonus, 2-martdan 10 kun, bosqichlar Avans 30% / Yetkazib berish 70%.

## Testlar

```bash
php artisan test        # task_test bazasida ishlaydi (phpunit.xml)
```

49 ta test: jarima misollari va qoidalari, buxgalteriya A–D misollari, to'lov tekshiruvlari tartibi,
bekor qilish, ruxsatlar, partiya qoldig'i va bir vaqtda ishlash (`ConcurrentPaymentTest` — ikkita alohida
PHP jarayoni bir bosqichni / bir oyning oxirgi qoldig'ini bir vaqtda to'laydi, faqat bittasi o'tadi).

## Kod tuzilishi

| Qatlam | Joyi |
|---|---|
| Hisob qoidalari (bazasiz, sof klasslar) | `app/Services/Penalty/PenaltyCalculator.php`, `app/Services/Accounting/StageLedger.php`, `app/Services/Accounting/PaymentRules.php`, `app/Support/Money.php` |
| Biznes amallari, tranzaksiya va qulflar | `app/Services/**/*Service.php` |
| Bazaga murojaat | `app/Repositories` |
| Qoldiqlar (smeta oyi, qator bo'yicha) | Modelda: `BudgetLine::remaining()`, `ContractItem::remainingPaid()` — SQL `withSum` bilan yuklanadi |
| Validatsiya va ruxsat | `app/Http/Requests`, `app/Policies` |
| Sahifalar | `app/Http/Controllers` (ingichka), `resources/views` |

Qarorlar va noaniq joylar bo'yicha tanlovlar — [ASSUMPTIONS.md](ASSUMPTIONS.md).

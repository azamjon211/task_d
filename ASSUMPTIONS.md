# Qarorlar va farazlar

TZ da ochiq qolgan joylar va ularga qabul qilingan qarorlar.

## Pul

- Float ishlatilmaydi. `App\Support\Money` — bcmath ustidagi value object, summa 2 xonali.
- Bazada pul PostgreSQL `numeric(15,2)` da saqlanadi va PHP ga satr bo'lib keladi (float ga aylanmaydi).
  `SUM()` lar ham `numeric` da — aniq.
- Yaxlitlash: 2 xonagacha, 0,005 va undan yuqori — yuqoriga (half-up). 2 xonadan ortiq kiritilgan summa
  jimgina yaxlitlanmaydi — rad etiladi.
- Foydalanuvchi summani `1 000,50` ko'rinishida ham kiritishi mumkin (bo'shliq va vergul qabul qilinadi).

## Shartnoma

- Faollashgandan keyin **butun shartnoma** o'zgarmaydi: sarlavha (sana, muddat ham — ular jarimaga ta'sir qiladi)
  va qatorlar.
- Qatorda pullik yoki bonus sonidan kamida bittasi > 0. Faqat bonusli qator bo'lishi mumkin.
- Faollashtirish uchun: kamida 1 qator, pullik summa > 0.
- To'lov bosqichlari TZ dagi jadvalga aynan teng va har bir shartnomaga avtomatik qo'yiladi: Avans 30% —
  faollashgandan keyin; Yetkazib berish 70% — pullik tovar to'liq kelgandan keyin. Nomi, foizi va sharti
  o'zgarmaydi. Ijrochi qoralamada har bir bosqichga ixtiyoriy muddat sanasini qo'yadi (boshlanish sanasidan
  oldin emas); faollashgandan keyin u ham o'zgarmaydi.
- Bosqich summasi faollashtirishda bir marta yoziladi (qatorlar endi o'zgarmaydi). Yaxlitlash farqi oxirgi
  bosqichga tushadi — bosqichlar yig'indisi pullik summaga aynan teng.
- Pullik tovar to'liq kelgan partiya bilan bir tranzaksiyada shartnoma Yakunlangan bo'ladi.

## Yetkazish

- Partiya sanasi shartnoma boshlanish sanasidan oldin va kelajakda bo'lishi mumkin emas.
- Pullik va bonus qoldig'i alohida tekshiriladi, xato aniq qator va maydon yonida chiqadi
  (masalan: «Paratsetamol»: pullik qoldiq 400 dona, siz 401 dona kiritdingiz).
- Yakunlangan shartnomaga ham partiya kiritish mumkin — faqat qolgan bonus uchun (pullik qoldiq 0, ortig'i rad etiladi).
- Partiyani tahrirlash/o'chirish yo'q (TZ da talab qilinmagan; jarima tarixini buzmaslik uchun).

## Jarima

- Qoralama shartnoma kuchga kirmagan — jarima hisoblanmaydi.
- **Hisoblash sanasi muddatdan oldin yoki muddat kuniga teng bo'lsa** — kechikish yo'q, jarima 0,00
  (limit baribir ko'rsatiladi).
- Muddat kunida kelgan partiya limitni ham kamaytiradi ("muddat kuni oxiridagi" kelmagan summa)
  va 4-qoidaga ko'ra keyingi kundan (muddatdan keyingi 1-kun) jarimani ham kamaytiradi.
- Kelmagan summa 0 bo'lgan kundan boshlab kunlar sanalmaydi (u qayta o'smaydi).
- Limit = muddat kuni oxiridagi kelmagan summa × 50%, half-up yaxlitlanadi.

### Boshliq qarori

- Jarima vaqt o'tishi bilan o'sadi, shuning uchun qaror **tanlangan hisoblash sanasidagi** summa bo'yicha
  qabul qilinadi va o'sha holat (sana + hisoblangan summa) saqlanadi. Summa serverda qayta hisoblanadi —
  formadan kelgan raqamga ishonilmaydi.
- Kelajakdagi sana bo'yicha qaror qabul qilinmaydi (u kunlar hali o'tmagan). Jarima 0 bo'lsa, qaror qabul qilinmaydi.
- Tasdiqlashda boshliq summani kamaytirishi mumkin: 0 < summa ≤ hisoblangan. Bo'sh qoldirilsa — hisoblangan summa.
- Rad etish uchun sabab majburiy.
- Qarorlar tarixi saqlanadi; **amaldagisi — eng oxirgisi**. Masalan, yetkazuvchi yana kechiksa, boshliq
  yangi sana bo'yicha qayta tasdiqlaydi; yoki avval tasdiqlangan jarimani keyin rad etishi mumkin.

## Buxgalteriya

### Jarimaning to'lovga ta'siri

- Faqat amaldagi qarori **tasdiqlangan** jarima hisobga olinadi (tasdiqlangan summa, bo'lmasa hisoblangan).
  Tasdiqlanmagan/rad etilgan — 0,00.
- Tasdiqlangan jarima **yakuniy (oxirgi) bosqich to'lovidan ushlab qolinadi**: oxirgi bosqich bo'yicha
  to'lanadigan summa = bosqich summasi − jarima. Masalan, 37,50 jarima bilan Yetkazib berish bosqichiga
  1712,50 to'lanadi va bosqich To'langan bo'ladi.
- Jarima oxirgi bosqich summasidan katta bo'lsa, ushlab qolinmagan qismi buxgalteriya sahifasida
  "Yetkazuvchidan undiriladigan jarima" sifatida ko'rsatiladi.
- **Jarima to'lovlar kiritilgandan keyin tasdiqlansa** (oxirgi bosqich allaqachon to'langan), reestr
  o'zgartirilmaydi: bosqichda "Ortiqcha to'langan" summa va yetkazuvchidan undiriladigan jarima ko'rsatiladi.
  Undirishni rasmiylashtirish (qaytarish hujjati) bu topshiriq doirasidan tashqarida.

### Bosqich holati

- Holat saqlanmaydi, har safar reestrdan (faol to'lovlar yig'indisi) hisoblanadi.
- Tartib: to'lash sharti bajarilmagan → Rejalashtirilgan; qoldiq 0 → To'langan; muddat sanasi o'tgan va qoldiq bor →
  Muddati o'tgan; qisman to'langan → Qisman to'langan; aks holda → To'lashga tayyor.
- **Muddati o'tgan** faqat to'lash sharti bajarilgan bosqichga qo'yiladi: shart bajarilmagan bo'lsa
  (masalan tovar kelmagan), bizda hali to'lash majburiyati yo'q.
- "Pullik tovar to'liq kelgan" sharti = shartnoma Yakunlangan holatida.

### To'lov kiritish

- Tekshiruvlar TZ dagi tartibda, qulf ostida bajariladi va birinchi xatoda to'xtaydi. Forma darajasida faqat
  ma'lumot shakli (raqam formati, sana formati, bosqich shu shartnomaniki) tekshiriladi; "summa > 0" ham
  4-qadamda — tartib buzilmasligi uchun.
- 3-qadam: **barcha** oldingi bosqichlar to'liq to'langan bo'lishi kerak (faqat bevosita oldingisi emas).
- 5-qadam: to'lov sanasining yili smeta qatorining moliyaviy yiliga teng bo'lishi kerak. Sananing oyi smeta
  oyiga teng bo'lishi shart emas — oyni buxgalter tanlaydi (TZ: "smeta qatori (oy)"). Sana kelajakda bo'lishi
  cheklanmagan (TZ da yo'q).

### Bekor qilish

- To'lov o'chirilmaydi va o'zgartirilmaydi (model darajasida ham taqiqlangan); faqat bir marta, sabab bilan
  bekor qilinadi. Qayta tiklash yo'q — kerak bo'lsa yangi to'lov kiritiladi.
- **Bekor qilingan to'lovning smeta oyi allaqachon o'tgan bo'lsa** ham summa o'sha oyning qoldig'iga qaytadi
  (qoldiq reestrdan hisoblanadi). O'tgan oy qatoridan yangi to'lov qilish taqiqlanmagan — TZ faqat yilni tekshiradi.
- Oldingi bosqich to'lovini keyingi bosqich to'langandan keyin ham bekor qilish mumkin — xatoni tuzatishni
  bloklamaslik uchun. Shundan so'ng keyingi bosqichlarga yangi to'lov oldingi bosqich qayta to'liq to'languncha
  qabul qilinmaydi.

### Smeta

- Smeta qatori: (yil, xarajat moddasi, oy) noyob. Ajratilgan summani shu oy bo'yicha to'langandan kam qilib
  bo'lmaydi (qoldiq manfiy bo'lmaydi). Reestrda (hatto bekor qilingan) to'lovi bor qator o'chirilmaydi.

## Ruxsatlar

- Hammasi serverda (Policy + FormRequest). Boshqa ijrochining shartnomasi ID bo'yicha ochilsa — **404**
  (shartnoma borligi ham oshkor qilinmaydi).
- Buxgalter barcha shartnomalarni faqat ko'radi; buxgalteriya sahifasi va smeta — faqat buxgalter.
- Boshliq hamma shartnomani ko'radi va jarima qarorini qabul qiladi; shartnoma yaratmaydi, to'lov kiritmaydi.

## Bir vaqtda ishlash

- Har bir yozish amali tranzaksiyada. Qulflar tartibi hamma joyda bir xil: shartnoma → smeta qatori → to'lov
  (deadlock bo'lmasligi uchun). Qulfdan keyingina qoldiqlar o'qiladi.
- PostgreSQL qator qulfi: `SELECT ... FOR UPDATE`. Ikkinchi tranzaksiya qulf bo'shashini kutadi va keyin
  (READ COMMITTED) yangilangan qoldiqni o'qiydi.
- Partiya kiritish, faollashtirish, jarima qarori ham shartnoma qulfi ostida.

## Vaqt

- Vaqt zonasi `Asia/Tashkent` (`APP_TIMEZONE`). "Bugun" — server sanasi.

## Qilinmagan / cheklovlar

- Qatorni tahrirlash formasi yo'q: qoralamada o'chirib, qayta qo'shiladi.
- Reestr va smeta ro'yxatlarida sahifalash (pagination) yo'q — demo hajmi uchun yetarli.

# AI Contract

الذكاء الاصطناعي مسؤول فقط عن:
1. تقييم المعايير الخمسة من 5.
2. حساب التقييم النهائي من 5.
3. استخراج نقاط القوة.
4. استخراج فرص التحسين.
5. اقتراح عنوان ووصف محسنين.

## Input
```json
{"title":"...","description":"...","department":"...","category":"..."}
```

## Output
```json
{
  "score": 4.2,
  "evaluation": {
    "innovation": 4.3,
    "feasibility": 4.0,
    "sustainability": 4.1,
    "cost": 3.9,
    "business_value": 4.5,
    "strengths": ["..."],
    "improvements": ["..."],
    "improvedTitle": "...",
    "improvedDescription": "..."
  }
}
```

لا يُطلب من AI حساب الإحصائيات، Top 5، الحالات، الإشعارات، أو قرارات المدير.

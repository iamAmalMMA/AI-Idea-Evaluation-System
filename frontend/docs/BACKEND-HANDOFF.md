# Backend Handoff

## Idea statuses
- `draft`: مسودة؛ خاصة بصاحب الفكرة ولا تظهر للمدير.
- `submitted`: بانتظار التحليل.
- `processing`: قيد تحليل الذكاء الاصطناعي.
- `evaluated`: تم التقييم وأصبحت نتائج AI جاهزة.
- `approved`: مرشحة للتنفيذ؛ يحددها المدير يدويًا لأي فكرة حالتها `evaluated`.
- `rejected`: مرفوضة؛ يستطيع المدير رفض أي فكرة وصلت إليه وليست مسودة.

## AI responsibilities only
1. Evaluate each of the five criteria out of 5.
2. Return the final score out of 5.
3. Return strengths.
4. Return improvement opportunities.
5. Return an improved idea title and description.

The application code—not AI—calculates analytics, averages, statuses, notifications, and Top 5. Top 5 is the five highest eligible AI final scores. Rejected ideas are excluded from performance averages and Top 5.

## Backend integration
Replace JSON persistence in `data/` with database repositories/services while keeping the UI contract stable. Replace the demo AI adapter with the real AI service and persist its five outputs.

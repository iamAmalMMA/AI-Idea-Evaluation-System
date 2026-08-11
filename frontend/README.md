# Smart Ideas Platform — Frontend Prototype

واجهة PHP عربية RTL لمنصة إدارة وتقييم الأفكار، ومهيأة للربط مع Backend وقاعدة بيانات وخدمة AI حقيقية.

## Structure
- `assets/` — CSS, JavaScript, images.
- `includes/` — shared PHP bootstrap, helpers, layout, permissions, and demo workflow logic.
- `pages/` — page views.
- `data/` — demo JSON data used only by the prototype.
- `database/schema.sql` — proposed database schema for backend integration.
- `docs/AI-CONTRACT.md` — exact AI input/output responsibility.
- `docs/BACKEND-HANDOFF.md` — backend handoff and workflow rules.
- `docs/INTEGRATION-CHECKLIST.md` — integration checklist.

## Local run
1. Install XAMPP.
2. Copy the project folder into `C:\xampp\htdocs\`.
3. Start Apache.
4. Open `http://localhost/<project-folder>/`.

## Final workflow
`draft → submitted → processing → evaluated`

After evaluation, the admin can:
- mark any `evaluated` idea as `approved` (مرشحة للتنفيذ), regardless of whether it is in Top 5; or
- reject an idea (`rejected`).

Top 5 remains an automatic ranking calculated by application code from the five highest eligible final AI scores. It does not control eligibility for nomination.

## AI scope
AI performs only:
1. Five criterion scores out of 5: Innovation, Feasibility, Sustainability, Cost, Business Value.
2. Final score out of 5.
3. Strengths.
4. Improvement opportunities.
5. Improved idea title and description.

Analytics, status changes, Top 5, permissions, and administrative decisions are calculated by application/backend code.

## Integration note
The current prototype uses JSON and a demo AI adapter. Backend developers should replace those adapters with production database/API services without changing the UI contract. Never commit real secrets; use `.env.example` as the template.

## Database
Import `database/schema.sql` into MySQL. The example DSN uses the `smart_ideas` database defined by that schema.

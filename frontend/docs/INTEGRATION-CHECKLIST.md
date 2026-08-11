# Integration Checklist

- [ ] Replace JSON storage with the production database.
- [ ] Implement employee authentication and admin authorization.
- [ ] Persist idea statuses: draft, submitted, processing, evaluated, approved, rejected.
- [ ] Allow `approved` only when the current status is `evaluated`; it is not limited to Top 5.
- [ ] Keep Top 5 as a code-calculated ranking of the five highest eligible final scores.
- [ ] Exclude rejected ideas from performance averages and Top 5.
- [ ] Connect AI for only the five documented outputs in `AI-CONTRACT.md`.
- [ ] Keep analytics computed by application/backend code from stored AI results.
- [ ] Store secrets in environment variables; never commit real `.env` credentials.
